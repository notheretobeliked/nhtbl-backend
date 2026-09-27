<?php

namespace App\Admin;

class SurveyExport
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'handle_export']);
    }

    public function add_admin_menu()
    {
        add_submenu_page(
            'edit.php?post_type=survey_response',
            'Export Survey Responses',
            'Export to CSV',
            'manage_options',
            'survey-export',
            [$this, 'admin_page']
        );
    }

    public function admin_page()
    {
        $surveys = $this->get_surveys_with_responses();
        
        ?>
        <div class="wrap">
            <h1>Export Survey Responses</h1>
            
            <?php if (empty($surveys)): ?>
                <div class="notice notice-info">
                    <p>No surveys with responses found.</p>
                </div>
            <?php else: ?>
                <form method="post" action="">
                    <?php wp_nonce_field('survey_export', 'survey_export_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="survey_id">Select Survey</label>
                            </th>
                            <td>
                                <select name="survey_id" id="survey_id" required>
                                    <option value="">Choose a survey...</option>
                                    <?php foreach ($surveys as $survey): ?>
                                        <option value="<?php echo esc_attr($survey['id']); ?>">
                                            <?php echo esc_html($survey['title']); ?> 
                                            (<?php echo $survey['response_count']; ?> responses)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">Only surveys with responses are shown.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="export_format">Export Format</label>
                            </th>
                            <td>
                                <select name="export_format" id="export_format">
                                    <option value="csv">CSV (Comma Separated)</option>
                                    <option value="excel">Excel Compatible CSV</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="export_survey" class="button-primary" value="Export to CSV">
                    </p>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }

    public function get_surveys_with_responses()
    {
        global $wpdb;
        
        // Get all survey responses with their referenced surveys
        $results = $wpdb->get_results("
            SELECT 
                p.ID as survey_id,
                p.post_title as survey_title,
                p.post_type as survey_type,
                COUNT(sr.ID) as response_count
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.meta_value
            INNER JOIN {$wpdb->posts} sr ON pm.post_id = sr.ID
            WHERE sr.post_type = 'survey_response'
            AND sr.post_status = 'publish'
            AND pm.meta_key = 'survey_reference'
            GROUP BY p.ID, p.post_title, p.post_type
            ORDER BY p.post_title ASC
        ");

        $surveys = [];
        foreach ($results as $result) {
            $surveys[] = [
                'id' => $result->survey_id,
                'title' => $result->survey_title . ' (' . ucfirst($result->survey_type) . ')',
                'response_count' => $result->response_count,
            ];
        }

        return $surveys;
    }

    public function handle_export()
    {
        if (!isset($_POST['export_survey']) || !wp_verify_nonce($_POST['survey_export_nonce'], 'survey_export')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $survey_id = intval($_POST['survey_id']);
        $export_format = sanitize_text_field($_POST['export_format']);

        if (!$survey_id) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Please select a survey to export.</p></div>';
            });
            return;
        }

        $this->export_csv($survey_id, $export_format);
    }

    public function export_csv($survey_id, $format)
    {
        // Get survey info
        $survey = get_post($survey_id);
        if (!$survey) {
            wp_die('Survey not found');
        }

        // Get all responses for this survey
        $responses = get_posts([
            'post_type' => 'survey_response',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'survey_reference',
                    'value' => $survey_id,
                    'compare' => '='
                ]
            ],
            'orderby' => 'date',
            'order' => 'ASC'
        ]);

        if (empty($responses)) {
            wp_die('No responses found for this survey');
        }

        // Prepare CSV data
        $csv_data = [];
        $headers = ['Response ID', 'Submitted At'];
        
        // Get all unique question keys to build headers
        $all_questions = [];
        foreach ($responses as $response) {
            $response_data = get_field('responses', $response->ID);
            if ($response_data) {
                foreach ($response_data as $answer) {
                    $question_key = $answer['question_key'];
                    if (!isset($all_questions[$question_key])) {
                        $all_questions[$question_key] = $answer['question_text'] ?: $question_key;
                    }
                }
            }
        }

        // Add question headers
        foreach ($all_questions as $key => $text) {
            $headers[] = $text . ' (' . $key . ')';
        }

        $csv_data[] = $headers;

        // Process each response
        foreach ($responses as $response) {
            $row = [
                $response->ID,
                get_field('submitted_at', $response->ID) ?: $response->post_date
            ];

            // Get response data
            $response_data = get_field('responses', $response->ID) ?: [];
            $answers = [];
            
            foreach ($response_data as $answer) {
                $question_key = $answer['question_key'];
                $answer_text = $answer['answer_text'] ?: $answer['answer'] ?: '';
                
                // Add other text if available
                if (!empty($answer['other_text'])) {
                    $answer_text .= ' (Other: ' . $answer['other_text'] . ')';
                }
                
                $answers[$question_key] = $answer_text;
            }

            // Add answers in the same order as headers
            foreach ($all_questions as $key => $text) {
                $row[] = $answers[$key] ?? '';
            }

            $csv_data[] = $row;
        }

        // Generate filename
        $filename = sanitize_file_name($survey->post_title) . '_responses_' . date('Y-m-d_H-i-s') . '.csv';

        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output CSV
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel compatibility if requested
        if ($format === 'excel') {
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        }

        foreach ($csv_data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }
}
