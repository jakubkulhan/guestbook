<?php
/**
 * Wraps callback with some default params
 */
class fn
{
    /**
     * @var callback Wrapped callback
     */
    private $callback;

    /**
     * @var array Wrapped callback params
     */
    private $params;

    /**
     * @var mixed Instance params placeholder
     */
    private $placeholder;

    /**
     * Constructor
     * @param callback to wrap
     * @param array
     * @param mixed instance params placeholder
     */
    public function __construct($callback = '', array $params = array())
    {
        $this->callback = $callback;
        $this->params = $params;
        if (func_num_args() === 3) $this->placeholder = func_get_arg(2);
        else $this->placeholder = self::ph(); 
            // if not given, use default placeholder
    }

    /**
     * Calls wrapped callback
     * @return mixed
     */
    public function __invoke()
    {
        $args = func_get_args();
        $params = array();
        foreach ($this->params as &$_)
            if ($_ === $this->placeholder) $params[] =& array_shift($args);
            else $params[] =& $_;
        return call_user_func_array($this->callback, $params);
    }

    /**
     * Default placeholder getter/setter
     * @return mixed
     */
    public static function ph()
    {
        static $placeholder;
        if (func_num_args() === 0) return $placeholder;
        else return $placeholder = func_get_arg(0);
    }
}

/**
 * Create new instance of fn a return fn::__invoke() callback
 * @param callback to wrap
 * @param array
 * @param mixed instance params placeholder
 * @return callback
 */
function fn($callback = '', array $params = array())
{
    if (func_num_args() < 3) $_ = new fn($callback, $params);
    else $_ = new fn($callback, $params, func_get_arg(2));
    return array($_, '__invoke');
}
