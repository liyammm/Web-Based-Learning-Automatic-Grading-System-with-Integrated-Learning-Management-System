<?php 


$message='';

if($_SERVER["REQUEST_METHOD"]== "POST"){ //checks if the request method is POST
    $Registration = new Users();

    //retrieves userinput from the form
    $first_name = $_POST['firstname']; //retrieves the first name entered by user
    $last_name = $_POST['lastname'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    //calss the registration method with the collected data 
     $message = $Registration->registerinfo($first_name,$last_name,$email,$username,$password,$role); 
  
    


}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Class Registration </title>
    <link rel="stylesheet" href="register.css">
</head>
<body>
<div>
<br>
    <h2>Class Registration</h2>
    <h3>Carefully answer the following</h3>   


    <form action="register.php" method = "POST">

<table>
    
    <tr>
        <div class="form-group">
            <td>
                <label for="fname">First Name</label>
            </td>
            <td><input type="text" id="fname" name="firstname" required></td>
            <td>
                <label for="lname">Last Name</label>
                
            </td>
            <td><input type="text" id="lname" name="lastname" required></td>
        </div>
    </tr>
 
<tr>
    <div class="form-group">
    <td><label for ="email">Email</label></td>
    <td><input type="email" id="email" name="email" required></td>
    <td><label for="username">Username</label></td>
    <td><input type="text" id="username" name="username" required></td>
</div>
</tr>
 

 
    <tr>
        <div class="form-group">
        <td><label for="password">Password</label></td>
        <td> <input type="password" id="password" name="password" required></td>
        <td><label for="role">Role</label></td>
        <td><select id="role" name="role" required style="width: 100%;" required>
            <option value="Student">Student</option>
            <option value="Teacher">Teacher</option>
    
        </select></td>
    </div>
    </tr>
</table>

    <input type="submit" value="Register">

    <?php if ($message): ?>
                <div class="message <?php echo strpos($message, 'error') !== false ? 'error' : (strpos($message, 'warning') !== false ? 'warning' : 'success'); ?>" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

    <p>Already have an account? <a href="login.php">Log in here </a></p>
    
    </form>
</body>
</html>