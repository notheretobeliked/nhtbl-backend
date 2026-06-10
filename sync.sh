#!/bin/bash
# orignal from https://discourse.roots.io/t/leveraging-wp-cli-aliases-in-your-wordpress-development-workflow/8414/12?u=allurewebsolutions

DEVDIR="web/app/uploads/"
DEVSITE="nhtbl-backend.test"

PRODDIR="cleavr@amna.nhtbl.studio:/home/cleavr/amna.nhtbl.studio/shared/uploads/"
PRODSITE="amna.nhtbl.studio"

# Drop a .sql / .sql.gz / .tar.gz / .gz dump in here to import from a local file
# instead of a live wp-cli export.
DB_IMPORTS_DIR=".db-imports"

FROM=$1
TO=$2

# Resolve the real host from a site value (rsync remote, URL, bare domain, or
# local path) so the confirmation banner shows where data is actually going.
extract_host() {
  local target="$1"

  # rsync remote format: user@host:/path
  if [[ "$target" == *"@"*":"* ]]; then
    local remote="${target#*@}"
    echo "${remote%%:*}"
    return
  fi

  # URL format: scheme://host[:port][/path]
  if [[ "$target" == *"://"* ]]; then
    local without_scheme="${target#*://}"
    local host_port="${without_scheme%%/*}"
    echo "${host_port%%:*}"
    return
  fi

  # Bare hostname/domain (no path separator) -> the host is the value itself
  if [[ "$target" != */* ]]; then
    echo "$target"
    return
  fi

  # Local filesystem path
  echo "localhost"
}

case "$1-$2" in
  dev-prod) DIR="up";   FROMSITE=$DEVSITE;  FROMDIR=$DEVDIR;  TOSITE=$PRODSITE; TODIR=$PRODDIR; ;;
  prod-dev) DIR="down"; FROMSITE=$PRODSITE; FROMDIR=$PRODDIR; TOSITE=$DEVSITE;  TODIR=$DEVDIR; ;;
  *) echo "usage: $0 {dev|prod} {dev|prod}" && exit 1 ;;
esac

FROM_SITE_HOST=$(extract_host "$FROMSITE")
TO_SITE_HOST=$(extract_host "$TOSITE")
FROM_UPLOAD_HOST=$(extract_host "$FROMDIR")
TO_UPLOAD_HOST=$(extract_host "$TODIR")

echo "=============================================================="
echo "WARNING: verify sync endpoints before continuing"
echo "  DB sync:      $FROM -> $TO"
echo "    from site:  $FROMSITE ($FROM_SITE_HOST)"
echo "    to site:    $TOSITE ($TO_SITE_HOST)"
echo "  Upload sync:  $FROMDIR ($FROM_UPLOAD_HOST) -> $TODIR ($TO_UPLOAD_HOST)"
echo "=============================================================="

# Check for local db import files
USE_LOCAL_DB="n"
if [[ -d "$DB_IMPORTS_DIR" ]]; then
  LATEST_DB=$(ls -t "$DB_IMPORTS_DIR"/*.{sql,sql.gz,tar.gz,gz} 2>/dev/null | head -n 1)
  if [[ -n "$LATEST_DB" ]]; then
    LATEST_DB_NAME=$(basename "$LATEST_DB")
    LATEST_DB_DATE=$(stat -f "%Sm" -t "%Y-%m-%d %H:%M" "$LATEST_DB")
    echo ""
    echo "Local database backup found:"
    echo "  $LATEST_DB_NAME (modified: $LATEST_DB_DATE)"
    read -r -p "Use this local file instead of live wp-cli export? [y/N] " USE_LOCAL_DB
  fi
fi

if [[ "$USE_LOCAL_DB" =~ ^([yY][eE][sS]|[yY])$ ]]; then
  response="y"
else
  read -r -p "Reset the $TO database and sync $DIR from $FROM? [y/N] " response
fi
read -r -p "Sync the uploads folder? [y/N] " uploads

if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
  echo "Exporting $TO db" &&
  wp "@$TO" db export $TO-backup.sql --path=web/wp &&
  echo "Resetting $TO db" &&
  wp "@$TO" db reset --yes --path=web/wp &&

  if [[ "$USE_LOCAL_DB" =~ ^([yY][eE][sS]|[yY])$ ]]; then
    echo "Using local database file: $LATEST_DB_NAME" &&

    # Determine how to extract the file based on extension
    if [[ "$LATEST_DB" == *.tar.gz ]]; then
      echo "Extracting tar.gz archive..." &&
      SQL_FILE=$(tar -tzf "$LATEST_DB" | grep '\.sql$' | head -n 1) &&
      if [[ -z "$SQL_FILE" ]]; then
        echo "Error: no .sql file found inside archive" && exit 1
      fi
      tar -xzf "$LATEST_DB" -C "$DB_IMPORTS_DIR" "$SQL_FILE" &&
      IMPORT_FILE="$DB_IMPORTS_DIR/$SQL_FILE"
    elif [[ "$LATEST_DB" == *.sql.gz || "$LATEST_DB" == *.gz ]]; then
      echo "Decompressing gz file..." &&
      IMPORT_FILE="${LATEST_DB%.gz}" &&
      gunzip -k "$LATEST_DB" 2>/dev/null || true
    else
      IMPORT_FILE="$LATEST_DB"
    fi &&

    # Strip MariaDB sandbox comment if present
    sed -i.bak '1s|^/\*M!999999\\- enable the sandbox mode \*/[[:space:]]*$||' "$IMPORT_FILE" &&
    rm -f "$IMPORT_FILE.bak" &&

    cat "$IMPORT_FILE" | wp "@$TO" db import - --path=web/wp &&

    # Clean up extracted file if it came from an archive
    if [[ "$LATEST_DB" == *.tar.gz && "$IMPORT_FILE" != "$LATEST_DB" ]]; then
      rm -f "$IMPORT_FILE"
    fi
  else
    echo "Exporting db from @$FROM to @$TO" &&
    # 2>/dev/null + grep strip PHP warnings and the MariaDB sandbox directive
    # that wp db import would otherwise choke on.
    wp "@$FROM" db export --path=web/wp - 2>/dev/null | grep -v -E "^(Deprecated:|Warning:|Notice:|/\*M!999999)" > ./temp_export_import.sql &&
    cat ./temp_export_import.sql | wp "@$TO" db import - --path=web/wp
  fi &&

  echo "Modifying $TO db" &&
  wp "@$TO" search-replace $FROMSITE $TOSITE --all-tables --precise --recurse-objects --skip-columns=guid --path=web/wp &&
  echo "Disabling automatic updates to prevent SSL errors" &&
  wp "@$TO" config set AUTOMATIC_UPDATER_DISABLED true --type=constant --path=web/wp &&
  wp "@$TO" config set WP_AUTO_UPDATE_CORE false --type=constant --path=web/wp
fi
if [[ "$uploads" =~ ^([yY][eE][sS]|[yY])$ ]]; then
  rsync -az --progress "$FROMDIR" "$TODIR"
fi
