<?php
/*
Plugin Name: Blog Retrospection
Plugin URI: https://wordpress.org/extend/plugins/blog-retrospection/ TODO Änderung derr Adresse
Description: This plugin generates a brief retrospection of your blog for a given timeframe (only year available in first release).
             See how many posts you wrote during this time, which were the most popular, who was the most active commenter etc.
             And then share the stats with your readers - copy the data to a new draft with a single click.

Version: 0.0.1
Author: Lioman
Text Domain: blog-retrospection
Author URI: http://www.lioman.de
License: GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html

    Copyright 2014  Elias Kirchgässner (email: dev [at] lioman.de)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

Min WP Version: 4.0
Max WP Version: 4.1
*/

add_action( 'admin_menu', 'retro_menu_add' );

$retro_translations = array( 'en' );
$retro_lang         = substr( WPLANG, 0, 2 );
if ( ! in_array( $retro_lang, $retro_translations ) ) {
    $retro_lang = 'en';
}

/* Translation */

$retro_trans[29] = array(
    'en' => array( 'your', 'you', '<p>&nbsp;</p>' )
);
$retro_trans[30] = array(
    'en' => array( 'the', 'I', '' )
);

function retro_menu_add() {
    add_submenu_page( 'index.php', 'Retrospection', 'Retrospection', 'read', 'retro', 'retro' );
}

function retro() {
    global $retro_trans, $retro_lang, $retro_translations;
    if ( ! current_user_can( 'read' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }


    echo '<div class="wrap">
        <!--<style type="text/css">
        .retroList{font-size:11px;margin-left:3em;list-style:disc;}
        #retroDonate{width:292px;height:420px;position:absolute;top:5px;right:5px;float:right;text-align:center;}
        .retroTable{margin: 0 0 0 10px;}
        .retroTable td{font-size:11px;line-height:2em;padding:0.25em;overflow:hidden;}
        .retroCol1{float:left;width:300px;overflow:hidden;}
        .retroCol2{float:left;width:260px;overflow:hidden;}
        .retroClear{clear:both;}
        </style>-->

            <h2>' . __( 'Retrospection - blogging summarized', 'blog-retrospection' ) . '</h2>

            <!--<div id="retroDonate">
                <iframe src="http://tools.flattr.net/widgets/thing.html?thing=1093328" width="292" height="420"></iframe>TODO /*Anpassen*/
            </div>-->
            <p>' . __( 'This plugin <strong>generates a retrospection of your blog</strong> for a given timeframe.',
            'blog-retrospection' ) . '</p>
            <p>' . __( 'See how many posts you wrote during the a choosen timeframe,  which were the most popular, who was the most active commenter etc.',
            'blog-retrospection' ) . '</p>
            <p>' . __( 'And then <strong>share the stats with your readers</strong> - copy the data to a new draft with a single click.',
            'blog-retrospection' ) . '</p>

    ';

    if ( $_POST['retro_generate'] == true ) {
        retro_generate( $_POST['retro_timeframe'] );//TODO übergabe

    } elseif ( $_POST['retro_draft'] == true ) {

        $my_post = array(
            'post_title'   => sprintf(__( 'Retrospection of %s', 'blog retrospection' ), $_POST['retro_timeframe']),
            'post_content' => base64_decode( $_POST['retro_draftcontent'] ),
            'post_status'  => 'draft',
            'post_author'  => get_current_user_id(),
        );

// Insert the post into the database
        $postid = wp_insert_post( $my_post );

        echo '<p>&nbsp;</p><div class="updated"><p>' . __( '<strong>A draft of the new post has been created</strong>. You can now',
                'blog-retrospection' ) . ' <a href="' . get_bloginfo(
                 'wpurl'
             ) . '/wp-admin/post.php?post=' . $postid . '&action=edit">' . __( 'edit it',
                'blog-retrospection' ) . '</a> ' . __( 'and then publish.', 'blog-retrospection' ) . '</p></div>';
        echo '<p>&nbsp;</p><p>&nbsp;</p>
              <form name="retro_generate" method="post" action="">
                <select name="retro_timeframe">' . retro_getTimeFrames() . '</select>

    <input type="submit" name="generateStats" class="button-primary" value="' . __( 'Regenerate retrospection',
                'blog-retrospection' ) . '" />
    <input type="hidden" name="retro_generate" value="TRUE" />
    </form>';

    } else {
        echo '<form name="retro_generate" method="post" action="">
     <select name="retro_timeframe">' . retro_getTimeFrames() . '</select>
    <input type="submit" name="generateStats" class="button-primary" value="' . __( 'Generate retrospection',
                'blog-retrospection' ) . '" />
    <input type="hidden" name="retro_generate" value="TRUE" />
    </form>';

    }

    echo '<p>&nbsp;</p><p>&nbsp;</p><hr><p><small>' . __( 'Do you have any questions or suggestions? Mail me: dev@lioman.de or get in contact on twitter: <a href="http://twitter.com/lioman" rel="nofollow">@lioman</a>. You can also check out my blog at <a href="http://www.lioman.de">www.lioman.de</a>',
            'blog-retrospection' ) . '</small></p>';
    echo '</div>';
}

function retro_getTimeFrames() {
    global $wpdb;
    $times      = $wpdb->get_results(
        "SELECT year(post_date) as years FROM $wpdb->posts WHERE post_status='publish'; "
    );
    $timeFrames = "";
    foreach ( $times as $option ) {
        $timeFrames = $timeFrames . '<option value="' . $option->years . '">' . $option->years . '</option>';
    }

    return $timeFrames;
}

function retro_generate( $timeframe ) {
    global $wpdb, $retro_trans, $retro_lang, $retro_translations;


    $retro_noposts = $wpdb->get_row(
        "SELECT count($wpdb->posts.ID) as howmany FROM $wpdb->posts WHERE year(post_date)=$timeframe and post_type='post' and post_status='publish'"
    );

    $retro_nopages = $wpdb->get_row(
        "SELECT count($wpdb->posts.ID) as howmany FROM $wpdb->posts WHERE year(post_date)=$timeframe and post_type='page' and post_status='publish'"
    );

    $retro_noattach = $wpdb->get_row(
        "SELECT count($wpdb->posts.ID) as howmany FROM $wpdb->posts WHERE year(post_date)=$timeframe and $wpdb->posts.post_type='attachment'"
    );

    $retro_noauthors = $wpdb->get_row( "SELECT count($wpdb->users.ID) as howmany FROM $wpdb->users" );

    $retro_nocomm = $wpdb->get_row(
        "SELECT count($wpdb->comments.comment_ID) as howmany FROM $wpdb->comments WHERE year($wpdb->comments.comment_date)=$timeframe and $wpdb->comments.comment_type!='trackback' and $wpdb->comments.comment_approved=1"
    );

    $retro_commbyauthors = $wpdb->get_results(
        "SELECT count($wpdb->comments.comment_ID) as howmany, $wpdb->comments.user_id FROM $wpdb->comments WHERE year($wpdb->comments.comment_date)=$timeframe and $wpdb->comments.comment_type!='trackback' and $wpdb->comments.comment_approved=1 and $wpdb->comments.user_id>0 group by $wpdb->comments.user_id"
    );

    $retro_nocommr = $wpdb->get_row(
        "SELECT count($wpdb->comments.comment_ID) as howmany FROM $wpdb->comments WHERE year($wpdb->comments.comment_date)=$timeframe and comment_type!='trackback' and $wpdb->comments.user_id>0 and $wpdb->comments.comment_approved=1"
    );

    $retro_months = $wpdb->get_results(
        "SELECT count($wpdb->posts.ID) as howmany, month($wpdb->posts.post_date) as postmonth FROM $wpdb->posts WHERE year(post_date)=$timeframe and $wpdb->posts.post_type='post' group by postmonth order by postmonth asc"
    );

    $retro_hours = $wpdb->get_results(
        "SELECT count($wpdb->posts.ID) as howmany, hour($wpdb->posts.post_date) as posthour FROM $wpdb->posts WHERE year(post_date)=$timeframe and $wpdb->posts.post_type='post' group by posthour order by posthour asc"
    );


    $retro_days = $wpdb->get_results(
        "SELECT count($wpdb->posts.ID) as howmany, dayname($wpdb->posts.post_date) as postday, dayofweek($wpdb->posts.post_date) as postday2 FROM $wpdb->posts WHERE year(post_date)=$timeframe and $wpdb->posts.post_type='post' group by postday order by postday2 asc"
    );


    $retro_postsbyauthors = $wpdb->get_results(
        "SELECT count($wpdb->posts.ID) as howmany, $wpdb->posts.post_author FROM $wpdb->posts WHERE year(post_date)=$timeframe and $wpdb->posts.post_type='post' group by $wpdb->posts.post_author order by howmany desc"
    );

    $retro_topcom = $wpdb->get_results(
        "SELECT $wpdb->posts.comment_count, $wpdb->posts.post_title, $wpdb->posts.ID FROM $wpdb->posts WHERE year($wpdb->posts.post_date)=$timeframe and $wpdb->posts.post_type='post' order by comment_count desc limit 10"
    );

    $retro_commenters = $wpdb->get_results(
        "SELECT count($wpdb->comments.comment_ID) as howmany,$wpdb->comments.comment_author FROM $wpdb->comments WHERE year($wpdb->comments.comment_date)=$timeframe and comment_type!='trackback' and $wpdb->comments.comment_approved=1 and user_id=0 group by $wpdb->comments.comment_author order by howmany desc limit 10"
    );

    $retro_commentsday = $wpdb->get_results(
        "SELECT count($wpdb->comments.comment_ID) as howmany, dayname($wpdb->comments.comment_date) as commentday, dayofweek($wpdb->comments.comment_date) as commentday2 FROM $wpdb->comments WHERE year($wpdb->comments.comment_date)=$timeframe and comment_type!='trackback' and $wpdb->comments.comment_approved=1 group by commentday order by commentday2 asc"
    );

    $retro_commentmonths = $wpdb->get_results(
        "SELECT count($wpdb->comments.comment_ID) as howmany, month($wpdb->comments.comment_date) as commentmonth FROM $wpdb->comments WHERE year($wpdb->comments.comment_date)=$timeframe and comment_type!='trackback' and $wpdb->comments.comment_approved=1 group by commentmonth order by commentmonth asc"
    );


    $retro_commenthours = $wpdb->get_results(
        "SELECT count($wpdb->comments.comment_ID) as howmany, hour($wpdb->comments.comment_date) as commenthour FROM $wpdb->comments WHERE year($wpdb->comments.comment_date)=$timeframe and comment_type!='trackback' and $wpdb->comments.comment_approved=1 group by commenthour order by commenthour asc"
    );

    foreach ( $retro_commenters as $retro_commenter ) {

        $retro_commentdata .= '<li>' . $retro_commenter->comment_author . ': <strong>' . $retro_commenter->howmany . '</strong> ' . __( 'comments',
                'blog-retrospection' ) . '</li>';
    }

    foreach ( $retro_months as $retro_month ) {
        if ( $retro_noposts->howmany != 0 ) {
            $retro_monthdata .= '<tr><td style="width:110px;text-align:right;font-weight:bold;">' . __(
                    date( "F", mktime( 0, 0, 0, $retro_month->postmonth, 1, $timeframe ) )
                ) . ':</td><td><div class="retroChartBar" style="width:' .
                                round(
                                    $retro_month->howmany / $retro_noposts->howmany * 70
                                ) . 'px"></div> &nbsp; ' . $retro_month->howmany . ' (' . round(
                                    $retro_month->howmany / $retro_noposts->howmany * 100,
                                    2
                                ) . '%)</td></tr>';
        }
    }

    foreach ( $retro_commentmonths as $retro_commentmonth ) {
        $retro_commentmonthdata .= '<tr><td style="width:110px;text-align:right;font-weight:bold;">' . __(
                date( "F", mktime( 0, 0, 0, $retro_commentmonth->commentmonth, 1, $timeframe ) )
            ) . ':</td><td><div class="retroChartBar" style="width:' . round(
                                       $retro_commentmonth->howmany / $retro_nocomm->howmany * 70
                                   ) . 'px"></div> &nbsp; ' . $retro_commentmonth->howmany . ' (' . round(
                                       $retro_commentmonth->howmany / $retro_nocomm->howmany * 100,
                                       2
                                   ) . '%)</td></tr>';
    }

    foreach ( $retro_hours as $retro_hour ) {
        if ( $retro_noposts->howmany != 0 ) {
            $retro_hourdata .= '<tr><td style="width:50px;text-align:right;font-weight:bold;">' . $retro_hour->posthour . ':</td><td><div class="retroChartBar" style="width:' . round(
                    $retro_hour->howmany / $retro_noposts->howmany * 70
                ) . 'px"></div> &nbsp; ' . $retro_hour->howmany . ' (' . round(
                                   $retro_hour->howmany / $retro_noposts->howmany * 100,
                                   2
                               ) . '%)</td></tr>';
        }
    }

    foreach ( $retro_commenthours as $retro_commenthour ) {
        $retro_commenthourdata .= '<tr><td style="width:50px;text-align:right;font-weight:bold;">' . $retro_commenthour->commenthour . ':</td><td><div class="retroChartBar" style="width:' . round(
                $retro_commenthour->howmany / $retro_nocomm->howmany * 70
            ) . 'px"></div> &nbsp; ' . $retro_commenthour->howmany . ' (' . round(
                                      $retro_commenthour->howmany / $retro_nocomm->howmany * 100,
                                      2
                                  ) . '%)</td></tr>';
    }

    foreach ( $retro_days as $retro_day ) {
        if ( $retro_noposts->howmany != 0 ) {
            $retro_daydata .= '<tr><td style="width:110px;text-align:right;font-weight:bold;">' . __(
                    $retro_day->postday
                ) . ':</td><td><div class="retroChartBar" style="width:' . round(
                                  $retro_day->howmany / $retro_noposts->howmany * 70
                              ) . 'px"></div> &nbsp; ' . $retro_day->howmany . ' (' . round(
                                  $retro_day->howmany / $retro_noposts->howmany * 100,
                                  2
                              ) . '%)</td></tr>';
        }
    }

    foreach ( $retro_commentsday as $retro_commentday ) {

        $retro_commentdaydata .= '<tr><td style="width:110px;text-align:right;font-weight:bold;">' . __(
                $retro_commentday->commentday
            ) . ':</td><td><div class="retroChartBar" style="width:' . round(
                                     $retro_commentday->howmany / $retro_nocomm->howmany * 70
                                 ) . 'px"></div> &nbsp; ' . $retro_commentday->howmany . ' (' . round(
                                     $retro_commentday->howmany / $retro_nocomm->howmany * 100,
                                     2
                                 ) . '%)</td></tr>';

    }

    foreach ( $retro_topcom as $retro_post ) {
        $retro_postdata .= '<li><a href="' . get_permalink(
                $retro_post->ID
            ) . '">' . $retro_post->post_title . '</a>: <strong>' . $retro_post->comment_count . '</strong> ' . __( 'comments',
                'blog-retrospection' ) . '</li>';
    }


    foreach ( $retro_postsbyauthors as $retro_author ) {
        $retro_authorprofile = get_userdata( $retro_author->post_author );
        $retro_authordata .= '<li>' . $retro_authorprofile->display_name . ': <strong>' . $retro_author->howmany . '</strong> ' . __( 'posts',
                'blog-retrospection' ) . '</li>';
    }

    $retro_commauthordata = "";
    foreach ( $retro_commbyauthors as $retro_commauthor ) {
        $retro_authorprofile2 = get_userdata( $retro_commauthor->user_id );
        $retro_commauthordata .= '<li>' . $retro_authorprofile2->display_name . ': <strong>' . $retro_commauthor->howmany . '</strong> ' . __( 'comments',
                'blog-retrospection' ) . '</li>';
    }


    $retro_text = "";
    $retro_text .= '
    <style type="text/css">.retroChartBar{height:15px;background:#1A87D5;display:inline-block;}</style>
    <p>' . sprintf(
            __( 'In %s you wrote <strong>%s</strong> posts and added <strong>%s pages</strong> to this blog, with <strong>%s attachments</strong> in total.',
                'blog-retrospection' ),
            $timeframe,
            $retro_noposts->howmany,
            $retro_nopages->howmany,
            $retro_noattach->howmany
        ) . '</p>
    <p>&nbsp;</p>
    <div class="retroCol1">
    <p><strong>' . __( 'The number of posts in each month', 'blog-retrospection' ) . ':</strong></p>
    <table class="retroTable">' . $retro_monthdata . '</table>

    <p>&nbsp;</p>

    <p><strong>' . __( 'The number of posts in each day of week', 'blog-retrospection' ) . ':</strong></p>
    <table class="retroTable">' . $retro_daydata . '</table>

    </div>
    <div class="retroCol2">
    <p><strong>' . __( 'Hours you publish new posts', 'blog-retrospection' ) . ':</strong></p>
    <table class="retroTable">' . $retro_hourdata . '</table>
    </div>
    <div class="retroClear"></div>
    <p>&nbsp;</p>
    <p>' . sprintf(
                       __( 'In %s your posts were commented <strong>%s</strong> times, from which <strong>%s</strong> comments (%s percent) were written by registered users/authors.',
                           'blog-retrospection' ),
                       $timeframe,
                       $retro_nocomm->howmany,
                       $retro_nocommr->howmany,
                       round( $retro_nocommr->howmany / $retro_nocomm->howmany * 100, 2 )
                   ) . '</p>
    <p>&nbsp;</p>
    <p><strong>' . sprintf(
                       __( 'TOP 10 commenters in %s', 'blog-retrospection' ),
                       $timeframe
                   ) . ':</strong></p>
    <ul class="retroList">' . $retro_commentdata . '</ul>
    <p>&nbsp;</p>
    <p><strong>' . sprintf(
                       __( 'TOP 10 most commented posts in %s', 'blog-retrospection' ),
                       $timeframe
                   ) . ':</strong></p>
    <ul class="retroList">' . $retro_postdata . '</ul>
    <p>&nbsp;</p>
    <div class="retroCol1">
    <p><strong>' . __( 'The number of comments in each month', 'blog-retrospection' ) . ':</strong></p>
    <table class="retroTable">' . $retro_commentmonthdata . '</table>
    <p>&nbsp;</p>
    <p><strong>' . __( 'Days people comment on your posts', 'blog-retrospection' ) . ':</strong></p>
    <table class="retroTable">' . $retro_commentdaydata . '</table>
    </div>
    <div class="retroCol2">
    <p><strong>' . __( 'At what hours people comment', 'blog-retrospection' ) . ':</strong></p>
    <table class="retroTable">' . $retro_commenthourdata . '</table>
    </div>
    <div class="retroClear"></div>
    ';

    if ( $retro_noauthors->howmany > 1 ) {
        $retro_text .= '<p>' . __( '<strong>This blog has more then one author.</strong> Here is the number of posts each one wrote:',
                'blog-retrospection' ) . '</p>
        <ul class="retroList">' . $retro_authordata . '</ul>
        <p>&nbsp;</p>
        <p>' . __( 'And the number of comments each one wrote:', 'blog-retrospection' ) . '</p>
        <ul class="retroList">' . $retro_commauthordata . '</ul>
        <p>&nbsp;</p>
        ';

    }


    $retro_draft = base64_encode(
        str_replace(
            $retro_trans[29][ $retro_lang ],
            $retro_trans[30][ $retro_lang ],
            $retro_text
        ) . '<p>' . __( 'Summary generated by <a href="https://wordpress.org/extend/plugins/TODO">blog retrospection plugin</a>' ) . '</p>'
    );

        echo '<p>&nbsp;</p><form name="retro_draft" method="post" action="">'
             . get_submit_button(  __( 'Create a new blog post with this retrospection data',
                'blog-retrospection' ), "primary", "generateDraft", false) .'
  <input type="hidden" name="retro_draft" value="TRUE" />
  <input type="hidden" name="retro_draftcontent" value="' . $retro_draft . '" />
  <input type="hidden" name="retro_timeframe" value="' . $timeframe . '" />
  </form>&nbsp;
    <div id="poststuff"><div class="postbox"><h3 class="hndle"><span>' . sprintf( __( 'Blog retrospection for %s',
                'blog-retrospection' ),
            $timeframe ) . '</span></h3><div class="inside"><p>&nbsp;</p>';
    echo $retro_text;

    echo '</div></div></div>';

}
