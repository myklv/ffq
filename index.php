<?php
ini_set('display_errors', 1);


/**
 * You can call all of these "requires" a table of contents
 */

require 'vendor/bento.php';
require 'vendor/Michelf/MarkdownExtra.inc.php';

require 'lib/http/g.php';            // fetch get/post variables
require 'lib/http/session.php';      // get and set session variables

require 'lib/page/filename.php';     // generate the relative path of a wiki page
require 'lib/page/pagename.php';     // format a pretty page name
require 'lib/page/page.php';         // ::store, ::fetch, and ::listall wiki pages

require 'lib/template/redlinks.php'; // highlight broken links
require 'lib/template/markdown.php'; // compile an extended markdown to html
require 'lib/template/render.php';   // render a php template
require 'lib/template/rtime.php';    // filter unix time into a relative format

require 'lib/user/auth.php';         // verify a user is logged in, or log them in


/**
 * General logic
 */

define('RECENT_VISITS', 10);

if (!file_exists("pages")) mkdir("pages");
if (!file_exists("pages/v")) mkdir("pages/v");

if (file_exists('private') && !in_array(substr(request_path(),1,1), ['=','-']))
    auth();


/**
 * Routes
 */

get('/', function() {
    render('list.php',
        ['name'=>"All Pages",'list'=>Page::listall(),'all'=>true]);
});

get('/-', function() {
    session_start();
    $_SESSION = [];
    session_destroy();
});

function login_page($_ = "") {
    if (request_method('POST')) {
        foreach (file("passwords") as $u) {
            if (trim($u) == g('user')." ".g('pass')) {
                session('user', g('user'));
                redirect("/".$_);
            }
        }

        flash('error', 'Access denied! Please try again.');
        flash('user', g('user'));
        redirect();
    }

    render('login.php',
        ['csrf_field'=>csrf_field(), 'user'=>flash('user')]);
}
form('/=', 'login_page');
form('/=<*:page>', 'login_page');


form('/:<*:page>', function($_) {
    auth();

    if (request_method('POST')) {
        if (Page::store(g("title"), g("content"),
           ['summary'=>g('summary'), 'author'=>session('user')])) 
        {
            if (g("title") != $_)
                unlink(filename($_));

            flash("alert", "Nice update!");
            redirect("/".g("title"));
        } else {
            flash("alert", "Something went wrong here... :-(");
            flash("text", g("content"));
            redirect();
        }
    }

    $page = Page::fetch($_);
    $text = g("text")? g("text") : ($page)? $page->text : "";

    render('edit.php', 
        ['csrf_field'=>csrf_field(), 'text'=>$text, 'page'=>$page, 'name'=>e($_)]);
});

form('/!<*:page>', function($_) {
    auth();

    $file = filename($_);

    if ( !is_link($file))
        halt(404);

    if (request_method('POST')) {
        if (unlink($file))
            flash("notice", "Successfully deleted $_");
        else
            flash("error", "Could not delete $_");

        redirect("/");
    }

    render('delete.php', ['csrf_field'=>csrf_field(), 'file'=>$file]);
});

get('/<*:page>~<#:version>', function($_, $v) {
    if ($f = Page::fetch($_, $v)) 
        render('view.php', 
            ['file'=>$f, 'name'=>e($_), 'newer'=>true]);
    else
        halt(404);
});

get('/<*:page>', function($_) {
    if (substr($_, -1) == "/" && $list = Page::listall($_))
        render('list.php', ['name'=>$_,'list'=>$list,'all'=>$_=='/']);

    elseif ($f = Page::fetch($_)) {
        if (! $stack = session('view_stack')) $stack = [];
        if (! $pos = g('pos'))                $pos = 0;

        if (reset($stack) != $_) {
            array_unshift($stack, $_);
            $stack = array_slice($stack, 0,RECENT_VISITS);
            session('view_stack', $stack);
        }

        render('view.php', 
            ['file'=>$f, 'name'=>e($_), 'pos'=>$pos, 'stack'=>$stack, 'newer'=>false]);
    } else
        halt(404);
});

return run(__FILE__);

