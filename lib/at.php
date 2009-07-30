<?php
class at
{
    /**
     * Text token
     */
    const TEXT = 1;

    /**
     * ``@" token
     */
    const AT = 2;

    /**
     * ``{" token
     */
    const LBRACE = 3;

    /**
     * ``}" token
     */
    const RBRACE = 4;

    /**
     * @var array Functions
     */
    private $fns = array();

    /**
     * @var callback Function not found handling
     */
    private $notfound = array(__CLASS__, '_notfound_');

    /**
     * @var callback Parse funnction name callback
     */
    private $fnname = array(__CLASS__, '_fnname_');

    /**
     * Construct
     * @param array functions
     */
    public function __construct(array $fns = array())
    {
        $this->fns = $fns;
    }

    /**
     * Function getter / setter / get all
     * @return mixed
     */
    public function fn()
    {
        $args = func_get_args();
        if (count($args) === 2) {
            if ($args[1] === NULL) {                            // unset
                unset($this->fns[$args[0]]);
            } else $this->fns[$args[0]] = $args[1];             // set
            return $this;
        } else if (count($args) === 1) {
            if (is_array($args[0])) {                           // set more 
                foreach ($args[0] as $k => $v) $this->fn($k, $v);
                return $this;
            } else return $this->fns[$args[0]];                 // get
        } else return $this->fns;                               // get all
    }

    /**
     * Function not found handling callback getter / setter
     */
    public function notfound()
    {
        $args = func_get_args();
        if (count($args) === 1) {                               // set
            $this->notfound = $args[0];
            return $this;
        } else return $this->notfound;                          // get
    }

    /**
     * Function name parse callback getter / setter
     */
    public function fnname()
    {
        $args = func_get_args();
        if (count($args) === 1) {                               // set
            $this->fnname = $args[0];
            return $this;
        } else return $this->fnname;                            // get
    }

    /**
     * Run given code
     * @param string|array code string or parsed tree
     * @return string|bool output of FALSE on failure
     */
    public function run($tree)
    {
        if (!is_array($tree)) $tree = self::parse($tree);
        if (!is_array($tree)) return FALSE;
        $ret = '';

        foreach ($tree as $_) {
            if (!is_array($_)) {
                $ret .= $_;
                continue;
            }

            list($fn, $params) = call_user_func($this->fnname, $_[0]);

            if (!isset($this->fns[$fn])) $ret .= call_user_func_array(
                $this->notfound, array_merge($params, array($_[1], $fn)));
            else if (is_callable($this->fns[$fn])) $ret .= call_user_func_array(
                $this->fns[$fn], array_merge($params, array($_[1], $fn)));
            else $ret .= $this->fns[$fn];
        }

        return $ret;
    }

    /**
     * Lex given string
     * @param string
     * @return array tokens
     */
    public static function lex($str)
    {
        $tokens = array();
        $i = 0;
        foreach (explode(chr(0), preg_replace('~(@|\{|\})~', chr(0) . '$1' . chr(0), 
            $str)) as $_)
        {
            $tokens[] = array(
                'type' => !($i & 1) 
                    ? self::TEXT 
                    : ($_ === '@' 
                        ? self::AT 
                        : ($_ === '{' ? self::LBRACE : self::RBRACE)),
                'content' => $_
            );
            $i++;
        }

        return $tokens;
    }

    /**
     * Parse given string
     * @param string
     * @return array abstract syntax tree
     */
    public function parse($str)
    {
        $tree = array(NULL, array());
        $indexes = array(0 => 0);
        $depth = 0;
        $braces_openend = 0;

        $tokens = self::lex($str);
        reset($tokens);

        while ($token = current($tokens)) {
            $current_depth = $depth;
            switch ($token['type']) {
                case self::TEXT:
                    $node = $token['content'];
                break;

                case self::AT:
                    $command = array();
                    $lbrace = NULL;
                    while (($_ = next($tokens)) && $lbrace === NULL) {
                        switch ($_['type']) {
                            case self::AT:
                            case self::TEXT:
                                $command[] = $_;
                            break;

                            case self::LBRACE:
                                $lbrace = $_;
                            break;

                            default: // rbrace, ...
                                return 'bad_cmd';
                        }
                    }
                    if ($lbrace === NULL) {
                        return 'bad_lbrace';
                    }
                    prev($tokens);
                    $node = array($command, array());
                    $depth++;
                    $indexes[$depth] = 0;
                break;

                case self::LBRACE:
                    $node = $token['content'];
                    $braces_openend++;
                break;

                case self::RBRACE:
                    if ($braces_openend === 0) {
                        $node = NULL;
                        $depth--;
                    } else {
                        $node = $token['content'];
                        $braces_openend--;
                    }
                break;

                default:
                    $node = NULL;
                    return 'bad_token';
            }

            if ($node !== NULL) {
                $indexes[$current_depth]++;
                $add =& $tree;
                for ($i = 0; $i < $current_depth; $i++) $add =& $add[1][$indexes[$i] - 1];
                $add[1][] = $node;
            }
            next($tokens);
        }

        if ($depth !== 0) {
            return 'bad_depth';
        }

        return $tree[1];
    }

    /**
     * Default function not found handling
     */
    public static function _notfound_()
    {
        $args = func_get_args();
        $fn = array_pop($args);
        $block = array_pop($args);
        throw new Exception('Unknown fn called ``' . $fn . '".');
    }

    /**
     * Default parse function name callback
     * @param array tokens
     * @return array array(fn, array(params))
     */
    public static function _fnname_($tokens)
    {
        $line = '';
        foreach ($tokens as $_) $line .= $_['content'];
        list($fn) = preg_split('~\s+~', trim($line));
        $rest = trim(substr($line, strlen($fn)));
        return array($fn, !empty($rest) 
            ? preg_split('~\s*,\s*~', $rest)
            : array());
    }
}

/**
 * at constuctor helper
 * @param array functions
 * @return at instance
 */
function at(array $fns = array())
{
    return new at($fns);
}
