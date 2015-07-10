# Introduction #

The DCIObject provides the core DCI functionality for PHP.  It creates an object that supports method injection and dynamic calling at runtime.  It works very similar to the way 'decorators' work, however the method of decoration and the issues of ambiguity are handled as well.

# Using DCIObjects #

To create an object that supports method injection, you need to create an object that extends DCIObject, and also make sure that the object calls the DCIObject constructor:

```
    class Account extends DCIObject {
        function __construct() {
            parent::__construct();
            
            /* SomeObject constructor code goes here */
        }
    }
```
Note that if no constructor code is needed, the construct() method may be omitted and DCIObject::construct will act as the object's default constructor:
```
    class Account extends DCIObject {}
```

And that's it!  Of course, simply extending DCIObject doesn't provide any injected methods, so to make the Account a real role-playing object, it needs to implement roles.

# Defining Roles and Role Methods #

Roles are nothing more than regular PHP interfaces coupled with a specially named class.  By convention, all roles should be prefixed with an 'r' rather than the interface standard 'i'.  The role must define the methods that an object needs to implement for it to play that role:

```
    interface rMoneySource {
        function Withdraw($amount);
    }
    interface rMoneySink {
        function Deposit($amount);
    }
```

Since roles define object interactions, they usually have a complementary role to facilitate some functionality.  In this case, we've defined roles that facilitate the transfer of money from one object to another.

Next, we need to specify role methods.  Role methods function on the concept that they will only call the methods specified in their interface.  In this situation, we'll write a role method that transfers money from an rMoneySource to an rMoneySink:

```
    class rMoneySourceActions {
        static function TransferFunds(rMoneySource $self, rMoneySink $dest, $amount) {
            if ($self->Withdraw($amount))
                $dest->Deposit($amount);
        }
    }
```

Note that all role methods are declared as **static** within a class that follows the convention of _rRoleName_ Actions { ... }.  Since role methods are not actually located inside of an object, they need to accept the source object as the first argument.  If this method were declared inside of a regular object as opposed to a role method, it would look like this:

```
    class Account ... {
        function TransferFunds($dest,$amount) {
            if ($this->Withdraw($amount)) {
                $dest->Deposit($amount);
            }
        }
    }
```

Notice that the definitions are different, however, the code to call each one is identical due to the method-injection performed in DCIObject:

```
    $account->TransferFunds($dest, 1000);
```

Still not convinced that DCI is useful?  Consider this: we want to now add a Wallet object to the program.  The wallet can also withdraw and deposit money, therefore, it can also act as an rMoneySource and rMoneySink.  Rather than writing another TransferFunds function within the Wallet object, we can just say that it plays the role of an rMoneySource:

```
    $wallet->TransferFunds($dest, 20);
```

In this way, we've separated the actual money transfer from the individual objects involved.    The TransferFunds method then becomes injected into any object that plays the role of an rMoneySource.

# Making an Object play a Role #

To make the method injection as transparent as possible, objects can play roles simply by using the standard PHP `implement` keyword.  Below is the full definition for the Account object (as defined in account\_example.php, which is included with the download):

```
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
```

# Context #

So what does it look like to actually use a role method?  DCI specifies that object interactions happen within a Context, so we need to create a Context (basically equivalent to a Use Case) to facilitate this interaction:

```
    class TransferCtx extends Context {
        function Execute(rMoneySource $source, rMoneySink $sink, $amount) {
            parent::Execute();
            $source->TransferFunds($sink,$amount);
        }
    }
```

Contexts are classes that extend the base Context class and provide an Execute(...) method.  Executing a context is very straightforward:

```
    $tm_ctx = new TransferCtx();
    $tm_ctx->Execute($checking, $savings, 1000);
```

As a rule of thumb, Contexts call role-methods and role-methods call object-methods.

## Role Method Ambiguity ##

It's possible that two roles will try to inject the same method, causing a problem with ambiguity.  This ambiguity is resolved by using type-hinting in a context's Execute method declaration.  Of course type-hinting is only half of the resolution.  A context's Execute method needs to make a call to parent::Execute() to actually resolve the ambiguity.

**Ambiguous Context -- (WRONG):**
```
    class TransferCtx extends Context {
        function Execute($source, $sink, $amount) {
            $source->TransferFunds($sink,$amount);
        }
    }
```

**Ambiguous Type-Hinted Context -- (WRONG):**
```
    class TransferCtx extends Context {
        function Execute(rMoneySource $source, rMoneySink $sink, $amount) {
            $source->TransferFunds($sink,$amount);
        }
    }
```

**Type-Hinted Context with call to parent::Execute() -- (RIGHT):**
```
    class TransferCtx extends Context {
        function Execute(rMoneySource $source, rMoneySink $sink, $amount) {
            parent::Execute();
            $source->TransferFunds($sink,$amount);
        }
    }
```

# Summary #

DCI provides a means to organize applications in a more flexible and maintainable way.  CoreDCI is an early implementation, but provides the basic libraries necessary to design an application using the DCI paradigm in PHP.  Currently, an application framework based on CoreDCI is being developed at http://code.google.com/p/waxphp .

For the source code to all examples in this article, download the source code for CoreDCI and look in tests/account\_example.php, which provides a full working example of CoreDCI.

For more information on DCI, check out:

[The Object-Composition Google Group](http://groups.google.com/group/object-composition/)

[The DCI Wikipedia Entry](http://en.wikipedia.org/wiki/Data,_Context,_and_Interaction)

[A video of Jim Coplien talking about DCI](http://architects.dzone.com/videos/dci-architecture-coplien)

[Trygve Reenskaug's DCI and BabyIDE site](http://heim.ifi.uio.no/~trygver/themes/babyide/babyide-index.html)