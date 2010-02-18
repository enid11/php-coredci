<?php
    /**
    * Context groups provide a means to implement several related
    * contexts in the form of methods.  For example:
    *
    * class CreatePostContext extends Context { ... }
    * class ReadPostContext extends Context { ... }
    * class ...PostContext extends Context { ... }
    * $ctxgroup = new CreatePostContext($arga,$argb,$argc);
    * $ctxgroup->Execute(); 
    *
    * As opposed to having it implemented as a context group:
    * class PostCtxGrp extends ContextGroup {
    *   function Create($arga, $argb, $argc) { ... }
    *   function Read($argd) { ... }
    * }
    * $ctxgroup = new PostCtxGrp("Create",$arga,$argb,$argc);
    * $result = $ctxgroup->Execute();
    * 
    */

    class ContextGroup extends Context {
        protected $exctx;
        protected $args;
    
        function __construct() {
            $args = func_get_args();
            $ctxname = array_shift($args);
        
            $this->exctx = $ctxname;
            $this->args = $args;
        }
        function Execute() {
            if (method_exists($this,$this->exctx)) {
                $call = $this->exctx;
                $args = $this->args;
            
                return call_user_func_array(array($this,$call),$args);
            }
            else throw new ContextNotFoundException($this->exctx, get_class($this));
        }
    }
?>