<?php

// add script in footer
add_action('wp_footer', 'md_add_script_wp_footer');
function md_add_script_wp_footer() {
?>
<script>
	jQuery( document ).ready(function() {
    jQuery(".sabai-alert.sabai-alert-success p").text('Your Business has been submitted successfully and published. You can view the Business'));
});
</script>

<?php
}

add_action('wp_head', 'your_function_name');
function your_function_name(){
?>
<script type="text/javascript" src="https://booking.resdiary.com/bundles/WidgetV2Loader.js"></script>

<?php
};




function add_shortcodes_inside_cm_entry_summary() {
    if (is_single()) {
        // First shortcode
        $shortcode_output_one = '<div class="single-ads-banner" style="margin-bottom: 20px;">' . do_shortcode('[smartslider3 slider="26"]') . '</div>';
        // Second shortcode
        $shortcode_output_two = '<div class="single-ads-banner" style="margin-bottom: 20px;">' . do_shortcode('[smartslider3 slider="27"]') . '</div>';

        // Inline JavaScript to insert the shortcodes in desired positions
        $script = "
            document.addEventListener('DOMContentLoaded', function() {
                var entrySummaryDiv = document.querySelector('.cm-entry-summary');
                if (entrySummaryDiv) {
                    // Insert after the last <p> tag
                    var lastParagraph = entrySummaryDiv.querySelector('p:last-of-type');
                    if (lastParagraph) {
                        var wrapperOne = document.createElement('div');
                        wrapperOne.innerHTML = `" . addslashes($shortcode_output_one) . "`;
                        lastParagraph.parentNode.insertBefore(wrapperOne.firstChild, lastParagraph.nextSibling);
                    }

                    // Insert after the third <p> tag
                    var paragraphs = entrySummaryDiv.querySelectorAll('p');
                    if (paragraphs.length >= 3) {
                        var thirdParagraph = paragraphs[2]; // Third <p> (index starts at 0)
                        var wrapperTwo = document.createElement('div');
                        wrapperTwo.innerHTML = `" . addslashes($shortcode_output_two) . "`;
                        thirdParagraph.parentNode.insertBefore(wrapperTwo.firstChild, thirdParagraph.nextSibling);
                    }
                }
            });
        ";

        // Add the inline script
        wp_add_inline_script('jquery', $script);
    }
}
add_action('wp_enqueue_scripts', 'add_shortcodes_inside_cm_entry_summary');




/* Simple Chatbot */
function enqueue_chatbot_script() {
    $version = filemtime(get_stylesheet_directory() . '/js/chatbot.js'); // Cache busting

    // Enqueue chatbot.js
    wp_enqueue_script(
        'chatbot-script',
        get_stylesheet_directory_uri() . '/js/chatbot.js',
        array('jquery'), // Dependencies
        $version,
        true
    );

    // Pass AJAX URL to chatbot.js
    wp_localize_script('chatbot-script', 'chatbotAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_chatbot_script');


// Search in post/page/directory
function chatbot_search_query() {
    if (!isset($_POST['message'])) {
        wp_send_json_error(['response' => 'Invalid request']);
        wp_die();
    }

    $search_query = sanitize_text_field($_POST['message']);

    // Extract important keywords from the user input
    $search_query = chatbot_extract_keywords($search_query);

    // Debugging: Log the final search query
    error_log('Chatbot Final Search Query: ' . $search_query);

    // Search WordPress posts and pages
    $args = array(
        'post_type'      => array('post', 'directory', 'page'),
        'posts_per_page' => 5,
        's'              => $search_query
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $results = [];
        while ($query->have_posts()) {
            $query->the_post();
            $results[] = '<a href="' . get_permalink() . '" target="_blank">' . get_the_title() . '</a>';
        }
        wp_reset_postdata();

        wp_send_json_success(['response' => 'Here are some related articles: <br>' . implode('<br>', $results)]);
    } else {
        wp_send_json_success(['response' => "Sorry, I couldn't find any relevant resources."]);
    }

    wp_die();
}

// Function to extract keywords
function chatbot_extract_keywords($text) {
    // Convert to lowercase and remove special characters
    $text = strtolower($text);
    $text = str_replace("’", "'", $text); // Fix curly apostrophes
    $text = preg_replace('/[^\w\s\']/', '', $text); // Remove punctuation except apostrophes

    // List of common words to ignore (stop words)
    $stop_words = [
        'what', 'how', 'where', 'about', 'to', 'is', 'a', 'an', 'the', 'can', 'i', 'you', 
        'know', 'want', 'need', 'please', 'tell', 'me', 'do', 'does', 'like'
    ];

    // Split sentence into words
    $words = explode(" ", $text);

    // Remove stop words
    $filtered_words = array_diff($words, $stop_words);

    // If we still have words left, return the most relevant keyword(s)
    if (!empty($filtered_words)) {
        return implode(" ", $filtered_words); // Return cleaned-up phrase
    }

    // If everything got removed, return original input as a fallback
    return $text;
}

// Register AJAX actions
add_action('wp_ajax_chatbot_search_query', 'chatbot_search_query');
add_action('wp_ajax_nopriv_chatbot_search_query', 'chatbot_search_query');
