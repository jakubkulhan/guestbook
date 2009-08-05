<?php
// import all libs
require_once 'lib/Nette/Tools.php';
foreach (Tools::glob(dirname(__FILE__) . '/lib/*.php') as $_) require_once $_;

// init
$table = dirname(__FILE__) . '/db/guestbook';

if (!is_dir(dirname($table)) && !mkdir(dirname($table), 0777, TRUE))
    die('Cannot create database :-(');

if (!file_exists($table) && !(
    (($_ = metatable::open($table)) && $_->close())))
        die('Cannot create table :-(');

if (!(${'@table'} = metatable::open($table, 
    $_SERVER['REQUEST_METHOD'] === 'GET' 
    ? metatable::READONLY
    : (metatable::READWRITE | metatable::STRINGS_GC))))
        die('Cannot open table :-(');

$_ = ${'@table'}->get('=', '*');
${'@all'} = array();
if (!empty($_)) ${'@all'} = array_keys(array_reverse(array_shift($_)));

${'@limit'} = 10;
${'@page'} = 1;
if (!empty($_SERVER['QUERY_STRING'])) ${'@page'} = intval($_SERVER['QUERY_STRING']);

// create form
${'@form'} = new Form;
${'@form'}->addText('name', 'Jméno:')
    ->addRule(Form::FILLED, 'Anonymy tu nechceme.');
${'@form'}->addtext('email', 'E-mail:')
    ->setEmptyValue('@')
    ->addCondition(Form::FILLED)
        ->addRule(Form::EMAIL, 'Podivný e-mail.');
${'@form'}->addText('www', 'WWW:')
    ->setEmptyValue('http://');
${'@form'}->addTextarea('text', 'Text:')
    ->addRule(Form::FILLED, 'Žádný text?');
${'@form'}['text']->getControlPrototype()->rows(2);
${'@form'}->addSubmit('ok', 'Přidat')
    ->onClick[] = '_add';
function _add()
{
    $now = date('Y-m-d H:i:s');
    foreach ($GLOBALS['@form']->getValues() as $k => $v) 
        $GLOBALS['@table']->set($now, $k, $v);
    $GLOBALS['@table']->set('=', $now, TRUE);
    $GLOBALS['@table']->close();

    header('HTTP/1.1 303 See Other');
    $request = new HttpRequest;
    header('Location: ' . $request->getOriginalUri()->getAbsoluteUri());
    exit();
}
${'@form'}->isSubmitted();

// at
${'@at'} = new at;

${'@at'}->fn('', fn('_echo', array(fn::ph())));
function _echo($block)
{
    $filters = preg_split('~\s*\|\s*~', $block[0]);
    $var = $GLOBALS['@' . array_shift($filters)];
    foreach ($filters as $filter) 
        $var = call_user_func($GLOBALS['@filter:' . $filter], $var);
    return $var;
}

${'@at'}->fn('each', fn('_each', array(fn::ph())));
function _each($block)
{
    $ret = '';

    foreach (array_slice($GLOBALS['@all'], ($GLOBALS['@page'] - 1) * $GLOBALS['@limit'], 
        $GLOBALS['@limit']) as $i) 
    {
        $values = $GLOBALS['@table']->get($i, '*');
        $values[$i]['date'] = strtotime($i);
        foreach ($values[$i] as $k => $v) $GLOBALS['@' . $k] = $v;
        $ret .= $GLOBALS['@at']->run($block);
    }

    return $ret;
}

${'@at'}->fn('pages', fn('_pages', array(fn::ph())));
function _pages($block)
{
    $ret = '';
    list($any, $current) = array_merge(array_filter($block, 'is_array'));

    for ($i = 1, $stop = ceil(count($GLOBALS['@all']) / $GLOBALS['@limit']); $i <= $stop; ++$i) {
        $GLOBALS['@i'] = $i;
        if ($i === $GLOBALS['@page']) {
            $ret .= $GLOBALS['@at']->run($current[1]);
        } else {
            $ret .= $GLOBALS['@at']->run($any[1]);
        }
    }

    return $ret;
}

${'@at'}->fn('on', fn('_on', array(FALSE, fn::ph(), fn::ph())));
${'@at'}->fn('on!', fn('_on', array(TRUE, fn::ph(), fn::ph())));
function _on($not, $cond, $block)
{
    if ($not === empty($GLOBALS['@' . trim($cond)])) return $GLOBALS['@at']->run($block);
}

// filters
${'@filter:texy'}   = fn(array(new Texy, 'process'),    array(fn::ph()));
${'@filter:escape'} = fn('htmlspecialchars',            array(fn::ph(), ENT_QUOTES));
${'@filter:date'}   = fn('date',                        array('j. n. Y, H:i:s', fn::ph()));
${'@filter:mailize'} = fn('_mailize',                   array(fn::ph(), FALSE));
${'@filter:mailto'} = fn('_mailize',                    array(fn::ph(), TRUE));
function _mailize($email, $mailto = FALSE)
{
    $ret = '';
    if ($mailto) $email = 'mailto:' . $email;

    for ($i = 0, $len = strlen($email); $i < $len; ++$i) {
        $ord = ord($email{$i});

        if ($ord <= 0x7F && $ord !== 64 /* @ */ && $ord !== 46 /* . */ && $i % 15 !== 0) {
            $ret .= chr($ord);
            continue;
        }

        if (!$mailto && $i & 1) $ret .= '<!---->';

        if ($ord <= 0x7F) $wchar = $ord;
        else if ($ord <= 0xC2) $wchar = FALSE;
        else if ($ord <= 0xDF) $wchar = ($ord & 0x1F) << 6 |
            (ord($email{++$i}) & 0x3F);
        else if ($ord <= 0xEF) $wchar = ($ord & 0x0F) << 12 |
            (ord($email{++$i}) & 0x3F) << 6 |
            (ord($email{++$i}) & 0x3F);
        else if ($ord <= 0xF4) $wchar = ($wchar & 0x0F) << 18 |
            (ord($email{++$i}) & 0x3F) << 12 |
            (ord($email{++$i}) & 0x3F) << 6 |
            (ord($email{++$i}) & 0x3F);
        else $wchar = FALSE;

        $ret .= '&#' . intval($wchar) . ';';
    }

    return $ret;
}

// run!
echo ${'@at'}->run(substr(file_get_contents(__FILE__), __COMPILER_HALT_OFFSET__));
__halt_compiler() ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Kniha návštěv</title>
        <style type="text/css">
        * { color: #888; font-family: sans-serif; }
        html { text-align: center; font-size: small; }
        body { width: 600px; margin: 50px auto; text-align: left; }
        h1 { text-align: center; font-family: "Trebuchet MS", serif; }
        .right { text-align: right; }
        .center { text-align: center; }
        hr { border: none; border-bottom: dotted #888 1px; }
        form table { width: 96%; }
        form td, form th { padding: 0 5px; }
        input[type=text], textarea { font-size: inherit; font-family: inherit; width: 100%; }
        a { color: #666; text-decoration: underline; }
        a:hover { text-decoration: none; background: #eee; }
        .warn, .err { color: #222; font-style: italic; }
        .err { font-weight: bold; }
        </style>
    </head>
    <body>
        <h1>Kniha návštěv</h1>
        @{form}

        @each {
            <hr>
            @{text | texy}
            <p class="right">
                @{date | date | escape} —
                @on  www   {<a href="@{www | escape}">@{name | escape}</a>}
                @on! www   {@{name | escape}}
                @on  email {&lt;<a href="@{email | mailto}">@{email | mailize}</a>&gt;}
            </p>
        }
        <hr>
        <p class="center">
        @pages {
            @{<a href="?@{i}">@{i}</a> }
            @{<strong>@{i}</strong> }
        }
        </p>
    </body>
</html>
