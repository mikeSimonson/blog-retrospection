<?php
namespace lioman\blog_retrospection;
/*
Plugin Name: Blog Retrospection
Plugin URI: https://wordpress.org/extend/plugins/blog-retrospection/ TODO Änderung der Adresse
Description: This plugin generates a brief retrospection of your blog for a given time segment (only year available in first release).
             See how many posts you wrote during this time, which were the most popular, who was the most active commenter etc.
             And then share the stats with your readers - copy the data to a new draft with a single click.

Version: 0.0.1
Author: Lioman
Author URI: http://www.lioman.de
Text Domain: blog-retrospection
Domain Path: /i18n/
License: http://www.gnu.org/licenses/gpl-3.0.txt

    Copyright 2015  Elias Kirchgässner (email: dev [at] lioman.de)


Min WP Version: 4.0
Max WP Version: 4.1
*/
define(__NAMESPACE__ . '\BR', __NAMESPACE__ . '\\');
add_action('init', BR . 'init');

/**
 * Initialize the plugin. adds menus, load text domain and JavaScript, etc.
 * @return void
 */
function init()
{
    add_action('admin_menu', BR . 'addMenus');
    add_action('plugins_loaded', BR . 'loadTextDomain');
    wp_localize_script('br_main', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    embedScriptsAndStyle();
    add_action('wp_ajax_api_getTimeSegmentData', BR . 'api_getTimeSegmentData');
}


/**
 * embeds jqplot and all needed plugins
 * @return void
 */
function embedScriptsAndStyle()
{
    wp_enqueue_script('jqplot', plugins_url('js/jqplot/jquery.jqplot.min.js', __FILE__), array('jquery'));
    wp_enqueue_script('jqplot_barRenderer', plugins_url('js/jqplot/plugins/jqplot.barRenderer.min.js', __FILE__), array('jqplot'));
    wp_enqueue_script('jqplot_axisRenderer', plugins_url('js/jqplot/plugins/jqplot.categoryAxisRenderer.min.js', __FILE__), array('jqplot'));
    wp_enqueue_script('jqplot_pointLabels', plugins_url('js/jqplot/plugins/jqplot.pointLabels.min.js', __FILE__), array('jqplot'));
    wp_enqueue_script('br_main', plugins_url('js/blog_retrospection.js', __FILE__), array('jquery', 'jqplot'));
    wp_enqueue_style('jqplot_style', plugins_url('css/jquery.jqplot.min.css', __FILE__));
    wp_enqueue_style('br_style', plugins_url('css/blog_retrospection.css', __FILE__));
}


function api_getTimeSegmentData()
{

    $intTimeSegment = intval($_POST['timeSegment']);

    echo getTimeSegmentData($intTimeSegment);

    wp_die(); // this is required to terminate immediately and return a proper response
}

function loadTextDomain()
{
    \load_plugin_textdomain('blog-retrospection', FALSE, basename(dirname(__FILE__)) . '/i18n/');
}


/**
 * Add menu to navigation
 * @return void
 */
function addMenus()
{
    \add_dashboard_page('Retrospection', 'Retrospection', 'publish_posts', 'blog_retrospection', BR . 'main');
}


/**
 * Get possible time segments from database and add option tags to them
 *
 * @return string HTML <option> String with time segments
 */
function getTimeSegmentsForDropDown()
{
    global $wpdb;
    $times = $wpdb->get_results(
        "SELECT DISTINCT year(post_date) as years FROM $wpdb->posts WHERE post_status='publish' ORDER BY years DESC; "
    );

    $timeSegments = "";

    foreach ($times as $option) {
        $timeSegments = $timeSegments . '<option value="' . $option->years . '">' . $option->years . '</option>';
    }

    return $timeSegments;
}


function createOptionsBox()
{
    echo '<div id="br_options_box">
    <h3>' . __('Options', 'blog-retrospection') . ':</h3>
    <form id="timeSegmentForm">
        <label for="timeSegmentDropDown">' . __('Time Segment:', 'blog-retrospection') . '</label>
        <select id="timeSegmentDropDown" onchange="timeSegmentSelected();">' . getTimeSegmentsForDropDown() . '</select>
     </form>
     <div id="br_check_boxes">
     <form>
     <p>' . __('Post and page count', 'blog-retrospection') . ':<input type="checkbox" id="checkboxPostCount"/><br /></p>
     <p>' . __('Posts per Month', 'blog-retrospection') . ':<input type="checkbox" id="checkboxPostPerMonth"/><br /></p>
     </form>
     </div>
     <div class="clear"></div>';

    echo '</div>';
}

function createGraphDivs()
{
    echo '
        <div id="chartPostCount" style="height:300px;width:400px; "></div>
        <div id="chartPostsPerMonth" style="height:300px;width:400px; "></div>
        ';
}


function main()
{
    if (!current_user_can('read')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    //\add_meta_box('howto-metaboxes-contentbox-1', 'Contentbox 1 Title', "content");

    echo '<div class="wrap">

            <h2>' . __('Retrospection - blogging summarized', 'blog-retrospection') . '</h2>

            <!--<div id="retroDonate">
                <iframe src="http://tools.flattr.net/widgets/thing.html?thing=1093328" width="292" height="420"></iframe>TODO /*Anpassen*/
            </div>-->
            <p>' . __('This plugin <strong>generates a retrospection of your blog</strong> for a given time segment.',
            'blog-retrospection') . '</p>
            <p>' . __('See how many posts you wrote during the a chosen time segment,  which were the most popular, who was the most active commenter etc.',
            'blog-retrospection') . '</p>
            <p>' . __('And then <strong>share the stats with your readers</strong> - copy the data to a new draft with a single click.',
            'blog-retrospection') . '</p>';
    createOptionsBox();
    createGraphDivs();


    /* ($_POST['retro_generate'] == true) {
        retro_generate($_POST['retro_timeSegment']);//TODO übergabe

    } elseif ($_POST['retro_draft'] == true) {

        $my_post = array(
            'post_title' => sprintf(__('Retrospection of %s', 'blog retrospection'), $_POST['retro_timeSegment']),
            'post_content' => base64_decode($_POST['retro_draftcontent']),
            'post_status' => 'draft',
            'post_author' => get_current_user_id(),
        );

// Insert the post into the database
        $postid = wp_insert_post($my_post);

        echo '<p>&nbsp;</p><div class="updated"><p>' . __('<strong>A draft of the new post has been created</strong>. You can now',
                'blog-retrospection') . ' <a href="' . get_bloginfo(
                'wpurl'
            ) . '/wp-admin/post.php?post=' . $postid . '&action=edit">' . __('edit it',
                'blog-retrospection') . '</a> ' . __('and then publish.', 'blog-retrospection') . '</p></div>';
        echo '<p>&nbsp;</p><p>&nbsp;</p>
              <form name="retro_generate" method="post" action="">
                <select name="retro_timeSegment">' . retro_getTimeSegments() . '</select>'
            . get_submit_button(__('Regenerate retrospection', 'blog-retrospection'),
                "primary",
                "generateStats",
                false) .
            '<input type="hidden" name="retro_generate" value="TRUE" />
              </form>';

    } else {
        echo '<form name="retro_generate" method="post" action="">
     <select name="retro_timeSegment">' . retro_getTimeSegments() . '</select>
    <input type="submit" name="generateStats" class="button-primary" value="' . __('Generate retrospection',
                'blog-retrospection') . '" />
    <input type="hidden" name="retro_generate" value="TRUE" />
    </form>';

    }
*/
    echo '<p>&nbsp;</p><p>&nbsp;</p><hr><p><small>' . __('Do you have any questions or suggestions? Mail me: dev@lioman.de or get in contact on twitter: <a href="http://twitter.com/lioman" rel="nofollow">@lioman</a>. You can also check out my blog at <a href="http://www.lioman.de">www.lioman.de</a>',
            'blog-retrospection') . '</small></p>';
    echo '</div>
<script>timeSegmentSelected()</script>';
}

/**
 * Gets all needed statistical data for a given time segment and return it as array
 *
 * @param $timeSegment
 *
 * @return array With all needed data for given time segment
 */
function getTimeSegmentData($timeSegment)
{
    global $wpdb;

    $timeSegmentData = array(
        'post_count' => array(
            "timeSegment" => $wpdb->get_var(
                "SELECT count($wpdb->posts.ID) FROM $wpdb->posts WHERE year(post_date)=$timeSegment and post_type='post' and post_status='publish'"
            ),
            "comparisonPeriod" => $wpdb->get_var("SELECT count($wpdb->posts.ID) FROM $wpdb->posts WHERE year(post_date)=$timeSegment-1 and post_type='post' and post_status='publish'"
            )
        ),
        "page_count" => array(
            "timeSegment" => $wpdb->get_var(
                "SELECT count($wpdb->posts.ID) as howmany FROM $wpdb->posts WHERE year(post_date)=$timeSegment and post_type='page' and post_status='publish'"
            ),
            "comparisonPeriod" => $wpdb->get_var(
                "SELECT count($wpdb->posts.ID) as howmany FROM $wpdb->posts WHERE year(post_date)=$timeSegment-1 and post_type='page' and post_status='publish'"
            )
        ),
        "average_posts" => $wpdb->get_var(
            "SELECT COUNT($wpdb->posts.ID) / COUNT(DISTINCT YEAR($wpdb->posts.post_date)) FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish'"
        ),
        "postsPerMonth" => array(
            "timeSegment" => $wpdb->get_results(
                "SELECT month($wpdb->posts.post_date) as postmonth,count($wpdb->posts.ID) as count FROM $wpdb->posts WHERE year(post_date)=$timeSegment and $wpdb->posts.post_type='post' group by postmonth order by postmonth asc"
            ),
            "comparisonPeriod" => $wpdb->get_results(
                "SELECT month($wpdb->posts.post_date) as postmonth,count($wpdb->posts.ID) as count FROM $wpdb->posts WHERE year(post_date)=$timeSegment-1 and $wpdb->posts.post_type='post' group by postmonth order by postmonth asc"
            )
        ),
        "author_count" => $wpdb->get_var("SELECT count($wpdb->users.ID) as howmany FROM $wpdb->users"),
        "comment_count" =>
            array(
                "timeSegment" => $wpdb->get_var(
                    "SELECT count($wpdb->comments.comment_ID) FROM $wpdb->comments WHERE year($wpdb->comments.comment_date)=$timeSegment and $wpdb->comments.comment_type!='trackback' and $wpdb->comments.comment_approved=1"
                ),
                "comparisonPeriod" => $wpdb->get_var(
                    "SELECT count($wpdb->comments.comment_ID) FROM $wpdb->comments WHERE year($wpdb->comments.comment_date)=$timeSegment-1 and $wpdb->comments.comment_type!='trackback' and $wpdb->comments.comment_approved=1"
                )
            )//,
        /*"retro_noattach" => $wpdb->get_var(
            "SELECT count($wpdb->posts.ID) as howmany FROM $wpdb->posts WHERE year(post_date)=$timeSegment and $wpdb->posts.post_type='attachment'"
        ),
        "retro_commbyauthors" => $wpdb->get_results(
            "SELECT count($wpdb->comments.comment_ID) as howmany, $wpdb->comments.user_id FROM $wpdb->comments WHERE year($wpdb->comments.comment_date)=$timeSegment and $wpdb->comments.comment_type!='trackback' and $wpdb->comments.comment_approved=1 and $wpdb->comments.user_id>0 group by $wpdb->comments.user_id"
        ),*/
        /*"retro_nocommr" => $wpdb->get_row(
            "SELECT count($wpdb->comments.comment_ID) as howmany FROM $wpdb->comments WHERE year($wpdb->comments.comment_date)=$timeSegment and comment_type!='trackback' and $wpdb->comments.user_id>0 and $wpdb->comments.comment_approved=1"
        ),
        "retro_hours" => $wpdb->get_results(
            "SELECT count($wpdb->posts.ID) as howmany, hour($wpdb->posts.post_date) as posthour FROM $wpdb->posts WHERE year(post_date)=$timeSegment and $wpdb->posts.post_type='post' group by posthour order by posthour asc"
        ),
        "retro_days" => $wpdb->get_results(
            "SELECT count($wpdb->posts.ID) as howmany, dayname($wpdb->posts.post_date) as postday, dayofweek($wpdb->posts.post_date) as postday2 FROM $wpdb->posts WHERE year(post_date)=$timeSegment and $wpdb->posts.post_type='post' group by postday order by postday2 asc"
        ),
        "retro_postsbyauthors" => $wpdb->get_results(
            "SELECT count($wpdb->posts.ID) as howmany, $wpdb->posts.post_author FROM $wpdb->posts WHERE year(post_date)=$timeSegment and $wpdb->posts.post_type='post' AND post_status='publish' group by $wpdb->posts.post_author order by howmany desc"
        ),
        "retro_topcom" => $wpdb->get_results(
            "SELECT $wpdb->posts.comment_count, $wpdb->posts.post_title, $wpdb->posts.ID FROM $wpdb->posts WHERE year($wpdb->posts.post_date)=$timeSegment AND post_type='post' AND post_status='publish' ORDER BY comment_count DESC limit 10"
        ),
        "retro_commenter" => $wpdb->get_results(
            "SELECT count($wpdb->comments.comment_ID) as howmany,$wpdb->comments.comment_author FROM $wpdb->comments WHERE year($wpdb->comments.comment_date)=$timeSegment AND comment_type!='trackback' AND $wpdb->comments.comment_approved=1 AND user_id=0 GROUP BY $wpdb->comments.comment_author order by howmany desc limit 10"
        ),
        "retro_commentsday" => $wpdb->get_results(
            "SELECT count($wpdb->comments.comment_ID) as howmany, dayname($wpdb->comments.comment_date) as commentday, dayofweek($wpdb->comments.comment_date) as commentday2 FROM $wpdb->comments WHERE year($wpdb->comments.comment_date)=$timeSegment and comment_type!='trackback' and $wpdb->comments.comment_approved=1 group by commentday order by commentday2 asc"
        ),
        "retro_commentmonths" => $wpdb->get_results(
            "SELECT count($wpdb->comments.comment_ID) as howmany, month($wpdb->comments.comment_date) as commentmonth FROM $wpdb->comments WHERE year($wpdb->comments.comment_date)=$timeSegment and comment_type!='trackback' and $wpdb->comments.comment_approved=1 group by commentmonth order by commentmonth asc"
        ),
        "retro_commenthours" => $wpdb->get_results(
            "SELECT count($wpdb->comments.comment_ID) as howmany, hour($wpdb->comments.comment_date) as commenthour FROM $wpdb->comments WHERE year($wpdb->comments.comment_date)=$timeSegment and comment_type!='trackback' and $wpdb->comments.comment_approved=1 group by commenthour order by commenthour asc"
        )*/
    );

    return json_encode($timeSegmentData);
}

/**
 * Main function to generate the statistics and create
 *
 * @param $timeSegment
 *
 */
function retro_generate($timeSegment)
{
    global $wpdb, $retro_trans, $retro_lang;

    //Get Data vor given time segment and for the preceding one.
    $timeSegmentData = retro_getTimeSegmentData($timeSegment);
    $compareSegmentData = retro_getTimeSegmentData($timeSegment - 1);


    foreach ($timeSegmentData['retro_commenter'] as $retro_commenter) {

        $retro_commentdata = '<li>' . $retro_commenter->comment_author . ': <strong>' . $retro_commenter->howmany . '</strong> ' . __('comments',
                'blog-retrospection') . '</li>';
    }

    foreach ($timeSegmentData['retro_months'] as $retro_month) {
        if ($timeSegmentData['retro_noposts']->howmany != 0) {
            $retro_monthdata = '<tr><td style="width:110px;text-align:right;font-weight:bold;">' . __(
                    date("F", mktime(0, 0, 0, $retro_month->postmonth, 1, $timeSegment))
                ) . ':</td><td><div class="retroChartBar" style="width:' .
                round(
                    $timeSegmentData['retro_month']->howmany / $timeSegmentData['retro_noposts']->howmany * 70
                ) . 'px"></div> &nbsp; ' . $timeSegmentData['retro_month']->howmany . ' (' . round(
                    $timeSegmentData['retro_month']->howmany / $timeSegmentData['retro_noposts']->howmany * 100,
                    2
                ) . '%)</td></tr>';
        }
    }

    foreach ($timeSegmentData['retro_commentmonths'] as $retro_commentmonth) {
        $retro_commentmonthdata = '<tr><td style="width:110px;text-align:right;font-weight:bold;">' . __(
                date("F", mktime(0, 0, 0, $timeSegmentData['retro_commentmonth']->commentmonth, 1, $timeSegment))
            ) . ':</td><td><div class="retroChartBar" style="width:' . round(
                $timeSegmentData['retro_commentmonth']->howmany / $timeSegmentData['retro_nocomm']->howmany * 70
            ) . 'px"></div> &nbsp; ' . $timeSegmentData['retro_commentmonth']->howmany . ' (' . round(
                $timeSegmentData['retro_commentmonth']->howmany / $timeSegmentData['retro_nocomm']->howmany * 100,
                2
            ) . '%)</td></tr>';
    }

    foreach ($timeSegmentData['retro_hours'] as $retro_hour) {
        if ($timeSegmentData['retro_noposts']->howmany != 0) {
            $retro_hourdata = '<tr><td style="width:50px;text-align:right;font-weight:bold;">' . $timeSegmentData['retro_hour']->posthour . ':</td><td><div class="retroChartBar" style="width:' . round(
                    $timeSegmentData['retro_hour']->howmany / $timeSegmentData['retro_noposts']->howmany * 70
                ) . 'px"></div> &nbsp; ' . $timeSegmentData['retro_hour']->howmany . ' (' . round(
                    $timeSegmentData['retro_hour']->howmany / $timeSegmentData['retro_noposts']->howmany * 100,
                    2
                ) . '%)</td></tr>';
        }
    }

    foreach ($timeSegmentData['retro_commenthours'] as $retro_commenthour) {
        $retro_commenthourdata = '<tr><td style="width:50px;text-align:right;font-weight:bold;">' . $timeSegmentData['retro_commenthour']->commenthour . ':</td><td><div class="retroChartBar" style="width:' . round(
                $timeSegmentData['retro_commenthour']->howmany / $timeSegmentData['retro_nocomm']->howmany * 70
            ) . 'px"></div> &nbsp; ' . $timeSegmentData['retro_commenthour']->howmany . ' (' . round(
                $timeSegmentData['retro_commenthour']->howmany / $timeSegmentData['retro_nocomm']->howmany * 100,
                2
            ) . '%)</td></tr>';
    }

    foreach ($timeSegmentData['retro_days'] as $retro_day) {
        if ($timeSegmentData['retro_noposts']->howmany != 0) {
            $retro_daydata = '<tr><td style="width:110px;text-align:right;font-weight:bold;">' . __(
                    $retro_day->postday
                ) . ':</td><td><div class="retroChartBar" style="width:' . round(
                    $retro_day->howmany / $timeSegmentData['retro_noposts']->howmany * 70
                ) . 'px"></div> &nbsp; ' . $retro_day->howmany . ' (' . round(
                    $retro_day->howmany / $timeSegmentData['retro_noposts']->howmany * 100,
                    2
                ) . '%)</td></tr>';
        }
    }

    foreach ($timeSegmentData['retro_commentsday'] as $retro_commentday) {

        $retro_commentdaydata = '<tr><td style="width:110px;text-align:right;font-weight:bold;">' . __(
                $retro_commentday->commentday
            ) . ':</td><td><div class="retroChartBar" style="width:' . round(
                $retro_commentday->howmany / $timeSegmentData['retro_nocomm']->howmany * 70
            ) . 'px"></div> &nbsp; ' . $retro_commentday->howmany . ' (' . round(
                $retro_commentday->howmany / $timeSegmentData['retro_nocomm']->howmany * 100,
                2
            ) . '%)</td></tr>';

    }

    foreach ($timeSegmentData['retro_topcom'] as $retro_post) {
        $retro_postdata = '<li><a href="' . get_permalink(
                $retro_post->ID
            ) . '">' . $retro_post->post_title . '</a>: <strong>' . $retro_post->comment_count . '</strong> ' . __('comments',
                'blog-retrospection') . '</li>';
    }


    foreach ($timeSegmentData['retro_postsbyauthors'] as $retro_author) {
        $retro_authorprofile = get_userdata($retro_author->post_author);
        $retro_authordata = '<li>' . $retro_authorprofile->display_name . ': <strong>' . $retro_author->howmany . '</strong> ' . __('posts',
                'blog-retrospection') . '</li>';
    }

    $retro_commauthordata = "";
    foreach ($timeSegmentData['retro_commbyauthors'] as $retro_commauthor) {
        $retro_authorprofile2 = get_userdata($retro_commauthor->user_id);
        $retro_commauthordata = '<li>' . $retro_authorprofile2->display_name . ': <strong>' . $retro_commauthor->howmany . '</strong> ' . __('comments',
                'blog-retrospection') . '</li>';
    }


    $retro_text = "";
    $retro_text .= '
    <style type="text/css">.retroChartBar{height:15px;background:#1A87D5;display:inline-block;}</style>
    <p>' . sprintf(
            __('In %s you wrote <strong>%s</strong> posts and added <strong>%s pages</strong> to this blog, with <strong>%s attachments</strong> in total.',
                'blog-retrospection'),
            $timeSegment,
            $timeSegmentData['retro_noposts']->howmany,
            $timeSegmentData['retro_nopages']->howmany,
            $timeSegmentData['retro_noattach']->howmany
        ) . '</p>
    <p>&nbsp;</p>
    <div class="retroCol1">
    <p><strong>' . __('The number of posts in each month', 'blog-retrospection') . ':</strong></p>
    <table class="retroTable">' . $retro_monthdata . '</table>

    <p>&nbsp;</p>

    <p><strong>' . __('The number of posts in each day of week', 'blog-retrospection') . ':</strong></p>
    <table class="retroTable">' . $retro_daydata . '</table>

    </div>
    <div class="retroCol2">
    <p><strong>' . __('Hours you publish new posts', 'blog-retrospection') . ':</strong></p>
    <table class="retroTable">' . $retro_hourdata . '</table>
    </div>
    <div class="retroClear"></div>
    <p>&nbsp;</p>
    <p>' . sprintf(
            __('In %s your posts were commented <strong>%s</strong> times, from which <strong>%s</strong> comments (%s percent) were written by registered users/authors.',
                'blog-retrospection'),
            $timeSegment,
            $timeSegmentData['retro_nocomm']->howmany,
            $timeSegmentData['retro_nocommr']->howmany,
            round($timeSegmentData['retro_nocommr']->howmany / $timeSegmentData['retro_nocomm']->howmany * 100,
                2)
        ) . '</p>
    <p>&nbsp;</p>
    <p><strong>' . sprintf(
            __('TOP 10 commenters in %s', 'blog-retrospection'),
            $timeSegment
        ) . ':</strong></p>
    <ul class="retroList">' . $retro_commentdata . '</ul>
    <p>&nbsp;</p>
    <p><strong>' . sprintf(
            __('TOP 10 most commented posts in %s', 'blog-retrospection'),
            $timeSegment
        ) . ':</strong></p>
    <ul class="retroList">' . $retro_postdata . '</ul>
    <p>&nbsp;</p>
    <div class="retroCol1">
    <p><strong>' . __('The number of comments in each month', 'blog-retrospection') . ':</strong></p>
    <table class="retroTable">' . $retro_commentmonthdata . '</table>
    <p>&nbsp;</p>
    <p><strong>' . __('Days people comment on your posts', 'blog-retrospection') . ':</strong></p>
    <table class="retroTable">' . $retro_commentdaydata . '</table>
    </div>
    <div class="retroCol2">
    <p><strong>' . __('At what hours people comment', 'blog-retrospection') . ':</strong></p>
    <table class="retroTable">' . $retro_commenthourdata . '</table>
    </div>
    <div class="retroClear"></div>
    ';

    if ($timeSegmentData['retro_noauthors']->howmany > 1) {
        $retro_text .= '<p>' . __('<strong>This blog has more then one author.</strong> Here is the number of posts each one wrote:',
                'blog-retrospection') . '</p>
        <ul class="retroList">' . $retro_authordata . '</ul>
        <p>&nbsp;</p>
        <p>' . __('And the number of comments each one wrote:', 'blog-retrospection') . '</p>
        <ul class="retroList">' . $retro_commauthordata . '</ul>
        <p>&nbsp;</p>
        ';

    }


    $retro_draft = base64_encode(
        str_replace(
            $retro_trans[29][$retro_lang],
            $retro_trans[30][$retro_lang],
            $retro_text
        //TODO correct URL
        ) . '<p>' . __('Summary generated by <a href="https://wordpress.org/extend/plugins/TODO">blog retrospection plugin</a>') . '</p>'
    );

    echo '<p>&nbsp;</p><form name="retro_draft" method="post" action="">'
        . get_submit_button(__('Create a new blog post with this retrospection data',
            'blog-retrospection'),
            "primary",
            "generateDraft",
            false) . '
  <input type="hidden" name="retro_draft" value="TRUE" />
  <input type="hidden" name="retro_draftcontent" value="' . $retro_draft . '" />
  <input type="hidden" name="retro_timeSegment" value="' . $timeSegment . '" />
  </form>&nbsp;
    <div id="poststuff"><div class="postbox"><h3 class="hndle"><span>' . sprintf(__('Blog retrospection for %s',
            'blog-retrospection'),
            $timeSegment) . '</span></h3><div class="inside"><p>&nbsp;</p>';
    echo $retro_text;

    echo '</div></div></div>';

}
