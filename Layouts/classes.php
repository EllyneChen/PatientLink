<?php
class MyClass {
    public function heading(){
        echo"Welcome to BBIT DevOps!";
    }
   public function myMethod(){
     echo "Hello, BBIT DevOps!";
   }
   public function footer(){
    echo"<footer>Contuct us at <a href='mailto:info@bbit.edu'>info@bbit.edu</a>";

   }
}
//create = an instance of MyClass
$instance = new myClass;

//call the method myMethod
$instance->heading();
$instance->myMethod();
$instance->footer();
