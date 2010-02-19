<?php
    // require the files needed for this test
    require_once ("../DCIObject.php");
    require_once ("../DCIException.php");
    require_once ("../Context.php");
        
    interface rMoneySource {
        function Withdraw($amount);
    }
    interface rMoneySink {
        function Deposit($amount);
    }
    
    class rMoneySourceActions {
        static function TransferFunds(rMoneySource $self, rMoneySink $dest, $amount) {
            if ($self->Withdraw($amount))
                $dest->Deposit($amount);
        }
    }
    
    /**
    * A base Account object.  It's a dumb model, capable only
    * of increasing and decreasing its balance.  We can use 
    * roles to make different Account objects interact with each 
    * other.
    *
    * Contexts are "use cases".  They call role methods to implement interactivity
    * Role methods are "algorithms".  They call various object methods to perform a task
    */
    class Account 
    extends DCIObject 
    implements rMoneySource, rMoneySink {
        protected $balance;
        
        function __construct($initial_balance) {
            parent::__construct();
            
            $this->balance = $initial_balance;
        }
        function Withdraw($amount) {
            if ($amount <= $this->balance) {
                $this->balance -= $amount;
                return $amount;
            }
            else 
                throw new DCIException("Insufficient Funds","Tried to withdraw $amount<br />{$this->balance} available.");
        }
        function Deposit($amount) {
            $this->balance += $amount;
        }
        function GetBalance() { return $this->balance; }
    }
    class FeeAccount
    extends Account {
        function Deposit($amount) {
            $this->balance += ($amount * .9);
        }
    }
    
    /** 
    * The example code-- implemented as a context
    */
    class TransferCtx extends Context {
        function Execute(rMoneySource $source, rMoneySink $sink, $amount) {
            $source->TransferFunds($sink,$amount);
        }
    }
    
    /** 
    * Run the context and display the change in values
    */
    $checking = new Account(1000);
    $savings = new FeeAccount(500);
    
    echo "<h3>Initialization Test:</h3>";
    echo "Checking Account: $" . $checking->GetBalance() . "<br />";
    echo "Savings Account: $" . $savings->GetBalance() . "<br /><br />";
    
    $tm_ctx = new TransferCtx();
    $tm_ctx->Execute($checking, $savings, 500);
    
    echo "<h3>Transaction Test:</h3>";
    echo "Checking Account: $" . $checking->GetBalance() . "<br />";
    echo "Savings Account: $" . $savings->GetBalance() . "<br /><br />";
    
    echo "<h3>Insufficient Funds Exception Test</h3>";
    echo "Insufficient Funds Test:<br />";
    $tm_ctx->Execute($checking, $savings, 1000);
?>