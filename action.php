<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$con = mysqli_connect('localhost', 'root', '', 'transport');

// Check connection
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit(); // Exit if connection fails
}

session_start();

if (isset($_POST['alogin'])) {
    $name = $_POST['uname'];
    $psw = $_POST['psw'];

    // Prepared statement for admin login
    $sql="SELECT * FROM admin WHERE uname='$name'AND psw='$psw'";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $name, $psw);
    mysqli_stmt_execute($stmt);
    $run = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($run) == 1) {
        $_SESSION['aname'] = $name;
        header('location:../admin.php');
        exit(); // Always exit after header redirect
    } else {
        echo "Invalid Admin Credentials.";
    }
    mysqli_stmt_close($stmt);
}

if (isset($_POST['signup'])) {
    $name = $_POST['name'];
    $uname = $_POST['uname'];
    $age = $_POST['age'];
    $pno = $_POST['pno']; // This variable is not used in the INSERT statement, but is present in the form input field 'aidno' so I'm keeping it for consistency in form fields. It should map to 'adhar_no' in the database.
    $aidno = $_POST['aidno']; // This seems to be the adhar_no
    $psw = $_POST['psw'];
    $email = $_POST['email'];

    // Check if username already exists
    $sql_check = "SELECT * FROM users WHERE uname=?";
    $stmt_check = mysqli_prepare($con, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "s", $uname);
    mysqli_stmt_execute($stmt_check);
    $run_check = mysqli_stmt_get_result($stmt_check);

    if (mysqli_num_rows($run_check) > 0) {
        echo "<h4>USERNAME ALREADY EXIST PLEASE ENTER VALID USERNAME</h4>";
    } else {
        // Insert new user
        $sql_insert = "INSERT INTO `users` (`name`, `uname`, `age`, `id_no`, `psw`, `email`) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_insert = mysqli_prepare($con, $sql_insert);
        mysqli_stmt_bind_param($stmt_insert, "ssisss", $name, $uname, $age, $aidno, $psw, $email);
        $run_insert = mysqli_stmt_execute($stmt_insert);

        if ($run_insert) {
            $_SESSION['uid'] = mysqli_insert_id($con);
            $_SESSION['uname'] = $uname;
            header('location:login.php');
            exit();
        } else {
            echo "Error: " . mysqli_error($con); // Display database error
        }
    }
    mysqli_stmt_close($stmt_check);
    if (isset($stmt_insert)) {
        mysqli_stmt_close($stmt_insert);
    }
}

if (isset($_POST['login'])) {
    $name = $_POST['uname'];
    $psw = $_POST['psw'];

    // Prepared statement for user login
    $sql = "SELECT uid FROM users WHERE uname=? AND psw=?";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $name, $psw);
    mysqli_stmt_execute($stmt);
    $run = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_array($run);

    if (mysqli_num_rows($run) == 1) {
        $_SESSION['uid'] = $row['uid'];
        $_SESSION['uname'] = $name;
        header('location:../profile.php');
        exit();
    } else {
        echo "Invalid Username or Password.";
    }
    mysqli_stmt_close($stmt);
}

if (isset($_POST['bus'])) {
    $bname = $_POST['bname'];
    $bno = $_POST['bno'];
    $from = $_POST['from'];
    $to = $_POST['to'];
    $time = $_POST['time'];
    $seat = $_POST['sno'];
    $type = $_POST['rad'];
    $fare = $_POST['fare'];

    // Check if bus details already exist
    $sql_check = "SELECT * FROM bus_details WHERE bno=? AND bfrom=?";
    $stmt_check = mysqli_prepare($con, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "ss", $bno, $from);
    mysqli_stmt_execute($stmt_check);
    $run_check = mysqli_stmt_get_result($stmt_check);

    if (mysqli_num_rows($run_check) > 0) {
        echo "<h4>BUS DETAILS ALREADY EXIST PLEASE ENTER VALID DETAILS.... THANKS</h4>";
    } else {
        // Insert new bus details
        $sql_insert = "INSERT INTO `bus_details` (`bname`, `bno`, `bfrom`, `bto`, `time`, `type`, `no_seat`, `fare`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = mysqli_prepare($con, $sql_insert);
        mysqli_stmt_bind_param($stmt_insert, "ssssssis", $bname, $bno, $from, $to, $time, $type, $seat, $fare);
        $run_insert = mysqli_stmt_execute($stmt_insert);

        if ($run_insert) {
            header('location:../admin.php');
            exit();
        } else {
            echo "Error: " . mysqli_error($con);
        }
    }
    mysqli_stmt_close($stmt_check);
    if (isset($stmt_insert)) {
        mysqli_stmt_close($stmt_insert);
    }
}

if (isset($_POST['search'])) {
    $from = $_POST['from'];
    $to = $_POST['to'];
    $date = $_POST['date'];

    // Search in booking_details first for buses with vacant seats
    $sql_booked = "SELECT * FROM booking_details WHERE jdate=? AND bfrom=? AND bto=? AND vacant > 0";
    $stmt_booked = mysqli_prepare($con, $sql_booked);
    mysqli_stmt_bind_param($stmt_booked, "sss", $date, $from, $to);
    mysqli_stmt_execute($stmt_booked);
    $run_booked = mysqli_stmt_get_result($stmt_booked);

    if (mysqli_num_rows($run_booked) > 0) {
        while ($row = mysqli_fetch_array($run_booked)) {
            $bus_id = $row['bus_id'];
            $vacant = $row['vacant'];

            $sql1 = "SELECT bname, bno, time, type, fare FROM bus_details WHERE bus_id=?";
            $stmt1 = mysqli_prepare($con, $sql1);
            mysqli_stmt_bind_param($stmt1, "i", $bus_id);
            mysqli_stmt_execute($stmt1);
            $run1 = mysqli_stmt_get_result($stmt1);
            $rows = mysqli_fetch_array($run1);

            if ($rows) {
                $bname = htmlspecialchars($rows['bname']);
                $bno = htmlspecialchars($rows['bno']);
                $time = htmlspecialchars($rows['time']);
                $type = htmlspecialchars($rows['type']);
                $fare = htmlspecialchars($rows['fare']);

                echo " <div class='card'>
                   <div class='card-header bg-info'>
                       <h3 class='text-center'>$bname</h3>
                   </div>
                  <div class='card-body bg-dark'>
                     <div class='row'>
                         <div class='col-md-6'>
                            <h5>Bus NO. :</h5>
                            <h5>From :</h5>
                            <h5>To :</h5>
                            <h5>Time :</h5>
                            <h5>Bus Type :</h5>
                             <h5>Fare :</h5>
                            <h5>Seat available :</h5>
                         </div>
                         <div class='col-md-6'>
                            <h5>$bno</h5>
                            <h5>$from</h5>
                            <h5>$to</h5>
                            <h5>$time</h5>
                            <h5>$type</h5>
                             <h5>$fare</h5>
                            <h5>$vacant</h5>
                         </div>
                     </div>
                  </div>
                  <div class='card-footer bg-danger'>
                      <div class='btn btn-outline-light book' bid='$bus_id' jid='$date' seat='$vacant'>Book ticket</div>
                  </div>
               </div>";
            }
            mysqli_stmt_close($stmt1);
        }
    } else {
        // If no booking_details found, search in bus_details
        $sql_bus = "SELECT * FROM bus_details WHERE bfrom=? AND bto=?";
        $stmt_bus = mysqli_prepare($con, $sql_bus);
        mysqli_stmt_bind_param($stmt_bus, "ss", $from, $to);
        mysqli_stmt_execute($stmt_bus);
        $run_bus = mysqli_stmt_get_result($stmt_bus);

        if (mysqli_num_rows($run_bus) > 0) {
            while ($row = mysqli_fetch_array($run_bus)) {
                $bus_id = $row['bus_id'];
                $bname = htmlspecialchars($row['bname']);
                $bno = htmlspecialchars($row['bno']);
                $time = htmlspecialchars($row['time']);
                $type = htmlspecialchars($row['type']);
                $fare = htmlspecialchars($row['fare']);
                $seat = htmlspecialchars($row['no_seat']);

                echo "<div class='card'>
                   <div class='card-header bg-info'>
                       <h3 class='text-center'>$bname</h3>
                   </div>
                  <div class='card-body bg-dark'>
                     <div class='row'>
                         <div class='col-md-6'>
                            <h5>Bus NO. :</h5>
                            <h5>From :</h5>
                            <h5>To :</h5>
                            <h5>Time :</h5>
                            <h5>Bus Type :</h5>
                             <h5>Fare :</h5>
                            <h5>Seat available :</h5>
                         </div>
                         <div class='col-md-6'>
                            <h5>$bno</h5>
                            <h5>$from</h5>
                            <h5>$to</h5>
                            <h5>$time</h5>
                            <h5>$type</h5>
                             <h5>$fare</h5>
                            <h5>$seat</h5>
                         </div>
                     </div>
                  </div>
                  <div class='card-footer bg-danger'>
                      <div class='btn btn-outline-light book' bid='$bus_id' jid='$date' seat='$seat'>Book ticket</div>
                  </div>
               </div>";
            }
        } else {
            echo "<h1 class='text-dark text-center'>OOps no bus found</h1>";
        }
        mysqli_stmt_close($stmt_bus);
    }
    mysqli_stmt_close($stmt_booked);
}

if (isset($_POST['book'])) {
    $jid = htmlspecialchars($_POST['jid']); // Sanitize output
    $bid = htmlspecialchars($_POST['bid']);
    $seat = htmlspecialchars($_POST['seat']);

    echo "  <div class='signup-content'>
                <form  id='signup-form' class='signup-form'>
                    <h2>Booking Detail </h2>
                     <div class='form-group'>
                        <input type='text' class='form-input' name='passenger_name' id='name' placeholder='Passenger Name' required />
                    </div>
                    <div class='form-group'>
                        <input type='number' class='form-input' name='num_passengers' id='no' placeholder='Number of passenger' required />
                    </div>
                    <div class='form-group'>
                      <a href='#s' class='btn btn-success booking' bid='$bid' jid='$jid' seat='$seat'>Conform Booking</a>
                    </div>
                </form>
            </div>";
}

if (isset($_POST['booking'])) {
    // Assuming these values are coming from the form dynamically, ensure they are properly handled
    $jid = $_POST['jid'];
    $bid = $_POST['bid'];
    $seat = $_POST['seat']; // total available seats at search time
    $name = $_POST['name']; // passenger name
    $no_p = $_POST['no_p']; // number of passengers for this booking

    if (!isset($_SESSION['uid'])) {
        echo "User not logged in.";
        exit();
    }
    $uid = $_SESSION['uid'];
    $date = date("Y-m-d");

    // Calculate vacant seats for this booking
    $vacant = $seat - $no_p;

    // Check if booking details already exist for this bus and journey date
    $sql_booking_details = "SELECT * FROM booking_details WHERE bus_id=? AND jdate=?";
    $stmt_booking_details = mysqli_prepare($con, $sql_booking_details);
    mysqli_stmt_bind_param($stmt_booking_details, "is", $bid, $jid);
    mysqli_stmt_execute($stmt_booking_details);
    $run_booking_details = mysqli_stmt_get_result($stmt_booking_details);

    if (mysqli_num_rows($run_booking_details) > 0) {
        // Update vacant seats in booking_details
        $sql_update_vacant = "UPDATE booking_details SET vacant=? WHERE bus_id=? AND jdate=?";
        $stmt_update_vacant = mysqli_prepare($con, $sql_update_vacant);
        mysqli_stmt_bind_param($stmt_update_vacant, "iis", $vacant, $bid, $jid);
        $run_update_vacant = mysqli_stmt_execute($stmt_update_vacant);

        if ($run_update_vacant) {
            // Get total seats from bus_details to calculate seat numbers
            $sql_bus_details = "SELECT no_seat FROM bus_details WHERE bus_id=?";
            $stmt_bus_details = mysqli_prepare($con, $sql_bus_details);
            mysqli_stmt_bind_param($stmt_bus_details, "i", $bid);
            mysqli_stmt_execute($stmt_bus_details);
            $run_bus_details = mysqli_stmt_get_result($stmt_bus_details);
            $row_bus_details = mysqli_fetch_array($run_bus_details);
            $tseat = $row_bus_details['no_seat'];
            mysqli_stmt_close($stmt_bus_details);

            $seatno = $tseat - $seat; // Starting point for seat numbering
            $tseatno_arr = [];
            for ($i = 0; $i < $no_p; $i++) {
                $seatno++;
                $tseatno_arr[] = $seatno;
            }
            $tseatno = implode(" ", $tseatno_arr); // Space-separated seat numbers

            // Check if ticket is already booked for this user, passenger name, journey date and bus
            $sql_check_ticket = "SELECT * FROM ticket WHERE uid=? AND pname=? AND jdate=? AND bus_id=?";
            $stmt_check_ticket = mysqli_prepare($con, $sql_check_ticket);
            mysqli_stmt_bind_param($stmt_check_ticket, "issi", $uid, $name, $jid, $bid);
            mysqli_stmt_execute($stmt_check_ticket);
            $run_check_ticket = mysqli_stmt_get_result($stmt_check_ticket);

            if (mysqli_num_rows($run_check_ticket) > 0) {
                echo "Your ticket is ALREADY booked, please check your ticket";
            } else {
                // Insert new ticket
                $sql_insert_ticket = "INSERT INTO `ticket` (`bus_id`, `uid`, `seat_no`, `no_seat`, `ticket_status`, `jdate`, `booking_date`, `pname`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_insert_ticket = mysqli_prepare($con, $sql_insert_ticket);
                mysqli_stmt_bind_param($stmt_insert_ticket, "iisissis", $bid, $uid, $tseatno, $no_p, $ticket_status = 'Conform', $jid, $date, $name);
                $run_insert_ticket = mysqli_stmt_execute($stmt_insert_ticket);

                if ($run_insert_ticket) {
                    echo "Your Ticket is Conformed .....Thank you";
                } else {
                    echo "Error booking ticket: " . mysqli_error($con);
                }
                mysqli_stmt_close($stmt_insert_ticket);
            }
            mysqli_stmt_close($stmt_check_ticket);
        } else {
            echo "Error updating vacant seats: " . mysqli_error($con);
        }
        mysqli_stmt_close($stmt_update_vacant);
    } else {
        // If no booking_details found, insert new entry and then book ticket
        $sql_bus_details_new = "SELECT no_seat, bfrom, bto FROM bus_details WHERE bus_id=?";
        $stmt_bus_details_new = mysqli_prepare($con, $sql_bus_details_new);
        mysqli_stmt_bind_param($stmt_bus_details_new, "i", $bid);
        mysqli_stmt_execute($stmt_bus_details_new);
        $run_bus_details_new = mysqli_stmt_get_result($stmt_bus_details_new);
        $row_bus_details_new = mysqli_fetch_array($run_bus_details_new);
        $tseat = $row_bus_details_new['no_seat'];
        $from = $row_bus_details_new['bfrom'];
        $to = $row_bus_details_new['bto'];
        mysqli_stmt_close($stmt_bus_details_new);

        $sql_insert_booking_details = "INSERT INTO `booking_details` (`bus_id`, `vacant`, `jdate`, `bfrom`, `bto`) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert_booking_details = mysqli_prepare($con, $sql_insert_booking_details);
        mysqli_stmt_bind_param($stmt_insert_booking_details, "iisss", $bid, $vacant, $jid, $from, $to);
        $run_insert_booking_details = mysqli_stmt_execute($stmt_insert_booking_details);

        if ($run_insert_booking_details) {
            $seatno = $tseat - $seat; // Starting point for seat numbering
            $tseatno_arr = [];
            for ($i = 0; $i < $no_p; $i++) {
                $seatno++;
                $tseatno_arr[] = $seatno;
            }
            $tseatno = implode(" ", $tseatno_arr);

            // Check if ticket is already booked (redundant check, but kept for logic consistency)
            $sql_check_ticket_new = "SELECT * FROM ticket WHERE uid=? AND pname=? AND jdate=? AND bus_id=?";
            $stmt_check_ticket_new = mysqli_prepare($con, $sql_check_ticket_new);
            mysqli_stmt_bind_param($stmt_check_ticket_new, "issi", $uid, $name, $jid, $bid);
            mysqli_stmt_execute($stmt_check_ticket_new);
            $run_check_ticket_new = mysqli_stmt_get_result($stmt_check_ticket_new);

            if (mysqli_num_rows($run_check_ticket_new) > 0) {
                echo "Your ticket is ALREADY booked please check your ticket";
            } else {
                // Insert new ticket
                $sql_insert_ticket_new = "INSERT INTO `ticket` (`bus_id`, `uid`, `seat_no`, `no_seat`, `ticket_status`, `jdate`, `booking_date`, `pname`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_insert_ticket_new = mysqli_prepare($con, $sql_insert_ticket_new);
                mysqli_stmt_bind_param($stmt_insert_ticket_new, "iisissis", $bid, $uid, $tseatno, $no_p, $ticket_status = 'Conform', $jid, $date, $name);
                $run_insert_ticket_new = mysqli_stmt_execute($stmt_insert_ticket_new);

                if ($run_insert_ticket_new) {
                    echo "Your Ticket is Conformed .....Thank you";
                } else {
                    echo "Error booking ticket: " . mysqli_error($con);
                }
                mysqli_stmt_close($stmt_insert_ticket_new);
            }
            mysqli_stmt_close($stmt_check_ticket_new);
        } else {
            echo "Error inserting booking details: " . mysqli_error($con);
        }
        mysqli_stmt_close($stmt_insert_booking_details);
    }
    mysqli_stmt_close($stmt_booking_details); // Close the initial check statement
}


if (isset($_POST['ticket'])) {
    if (!isset($_SESSION['uid'])) {
        echo "<h4>Please log in to view your tickets.</h4>";
        exit();
    }
    $uid = $_SESSION['uid'];

    // Select tickets for the logged-in user
    $sql_ticket = "SELECT * FROM ticket WHERE uid=?";
    $stmt_ticket = mysqli_prepare($con, $sql_ticket);
    mysqli_stmt_bind_param($stmt_ticket, "i", $uid);
    mysqli_stmt_execute($stmt_ticket);
    $run_ticket = mysqli_stmt_get_result($stmt_ticket);

    if (mysqli_num_rows($run_ticket) == 0) {
        echo "<h4>First Book the ticket</h4>";
    } else {
        while ($row = mysqli_fetch_array($run_ticket)) {
            $bid = $row['bus_id'];
            $seat_no = htmlspecialchars($row['seat_no']);
            $no_seat = htmlspecialchars($row['no_seat']);
            $ticket_status = htmlspecialchars($row['ticket_status']);
            $jdate = htmlspecialchars($row['jdate']);
            $booking_date = htmlspecialchars($row['booking_date']);
            $pname = htmlspecialchars($row['pname']);

            // Get user details
            $sql_user = "SELECT age, id_no, email FROM users WHERE uid=?";
            $stmt_user = mysqli_prepare($con, $sql_user);
            mysqli_stmt_bind_param($stmt_user, "i", $uid);
            mysqli_stmt_execute($stmt_user);
            $run_user = mysqli_stmt_get_result($stmt_user);
            $rows_user = mysqli_fetch_array($run_user);
            mysqli_stmt_close($stmt_user);

            $age = htmlspecialchars($rows_user['age']);
            $adhar_no = htmlspecialchars($rows_user['adhar_no']);
            $email = htmlspecialchars($rows_user['email']);

            // Get bus details
            $sql_bus_details = "SELECT bname, bno, bfrom, bto, time, fare FROM bus_details WHERE bus_id=?";
            $stmt_bus_details = mysqli_prepare($con, $sql_bus_details);
            mysqli_stmt_bind_param($stmt_bus_details, "i", $bid);
            mysqli_stmt_execute($stmt_bus_details);
            $run_bus_details = mysqli_stmt_get_result($stmt_bus_details);
            $row1 = mysqli_fetch_array($run_bus_details);
            mysqli_stmt_close($stmt_bus_details);

            $bname = htmlspecialchars($row1['bname']);
            $bno = htmlspecialchars($row1['bno']);
            $bfrom = htmlspecialchars($row1['bfrom']);
            $bto = htmlspecialchars($row1['bto']);
            $time = htmlspecialchars($row1['time']);
            $fare = htmlspecialchars($row1['fare']);
            $Tfare = $fare * $no_seat;

            echo "<div class='card'>
    <div class='card-header bg-info'>
      <h3 class='text-center'>Ticket Detail</h3>
    </div>
    <div class='card-body bg-dark'>
      <div class='card bg-dark '>
         <h2  class='text-center text-white'>Passenger Detail</h2><hr>
       <div class='row'>
         <div class='col-md-6'>
           <h4 class=' text-white'>Passenger Name :</h4>
           <h4 class='text-white'>Adhar Card No :</h4>
           <h4 class='text-white'>Age :</h4>
           <h4 class='text-white'>Email :</h4>
         </div>
         <div class='col-md-6'>
           <h4 class=' text-white'>$pname </h4>
           <h4 class='text-white'>$adhar_no </h4>
           <h4 class='text-white'>$age</h4>
           <h4 class='text-white'>$email</h4>
         </div>
       </div>
      </div>
      <div class='card bg-dark '>
         <h2  class='text-center text-white'>Bus Detail</h2><hr>
       <div class='row'>
         <div class='col-md-6'>
           <h4 class=' text-white'>Bus Name :</h4>
           <h4 class='text-white'>Bus No :</h4>
           <h4 class='text-white'>Time :</h4>
           <h4 class='text-white'>From :</h4>
          <h4 class='text-white'>To :</h4>
         </div>
         <div class='col-md-6'>
           <h4 class=' text-white'>$bname</h4>
           <h4 class='text-white'>$bno</h4>
           <h4 class='text-white'>$time</h4>
           <h4 class='text-white'>$bfrom</h4>
          <h4 class='text-white'>$bto</h4>
         </div>
       </div>
      </div>
       <div class='card bg-dark '>
         <h2  class='text-center text-white'>Ticket Detail</h2><hr>
       <div class='row'>
         <div class='col-md-6'>
           <h4 class=' text-white'>Number Of Seat :</h4>
           <h4 class='text-white'>Seat No :</h4>
           <h4 class='text-white'>Status :</h4>
           <h4 class='text-white'>Fare :</h4>
           <h4 class='text-white'>Journey Date :</h4>
          <h4 class='text-white'>Booking Date :</h4>
         </div>
         <div class='col-md-6'>
           <h4 class=' text-white'>$no_seat</h4>
           <h4 class='text-white'>$seat_no</h4>
           <h4 class='text-white'>$ticket_status</h4>
           <h4 class='text-white'>$Tfare</h4>
          <h4 class='text-white'>$jdate</h4>
          <h4 class='text-white'>$booking_date</h4>
         </div>
       </div>
      </div>
    </div>
    <div class='card-footer bg-info'>
     <a href='profile.php' class='btn btn-outline-danger'>Home</a>
    </div>
  </div>";
        }
    }
    mysqli_stmt_close($stmt_ticket);
}

if (isset($_POST['bkd'])) { // Bus KiloMeter Details or Booked KiloMeter Details
    $bid = $_POST['bid'];

    // Get bus details to find total seats
    $sql_bus_details = "SELECT bname, no_seat, time FROM bus_details WHERE bus_id=?";
    $stmt_bus_details = mysqli_prepare($con, $sql_bus_details);
    mysqli_stmt_bind_param($stmt_bus_details, "i", $bid);
    mysqli_stmt_execute($stmt_bus_details);
    $run_bus_details = mysqli_stmt_get_result($stmt_bus_details);
    $row1 = mysqli_fetch_array($run_bus_details);
    mysqli_stmt_close($stmt_bus_details);

    $bname = $row1 ? htmlspecialchars($row1['bname']) : "N/A";
    $seat = $row1 ? htmlspecialchars($row1['no_seat']) : 0;
    $time = $row1 ? htmlspecialchars($row1['time']) : "N/A";


    // Get booking details for the specific bus
    $sql_booking = "SELECT vacant, jdate, bfrom, bto FROM booking_details WHERE bus_id=?";
    $stmt_booking = mysqli_prepare($con, $sql_booking);
    mysqli_stmt_bind_param($stmt_booking, "i", $bid);
    mysqli_stmt_execute($stmt_booking);
    $run_booking = mysqli_stmt_get_result($stmt_booking);

    if (mysqli_num_rows($run_booking) == 0) {
        echo "<h4 class='text-center text-white'>Result Not Found</h4>";
    } else {
        while ($row = mysqli_fetch_array($run_booking)) {
            $vacant = htmlspecialchars($row['vacant']);
            $booked = $seat - $vacant; // Calculate booked seats
            $jdate = htmlspecialchars($row['jdate']);
            $bfrom = htmlspecialchars($row['bfrom']);
            $bto = htmlspecialchars($row['bto']);

            echo " <div class='row'>
          <div class='col-md-2'>
            <h3 class='text-center text-white'> $bname</h3>
          </div>
          <div class='col-md-2'>
            <h3 class='text-center text-white'>$booked</h3>
          </div>
          <div class='col-md-2'>
            <h3 class='text-center text-white'>$vacant</h3>
          </div>
          <div class='col-md-2'>
            <h3 class='text-center text-white'>$bfrom</h3>
          </div>
          <div class='col-md-2'>
            <h3 class='text-center text-white'>$bto</h3>
          </div>
          <div class='col-md-2'>
            <h3 class='text-center text-white'>$jdate</h3>
          </div>
        </div>";
        }
    }
    mysqli_stmt_close($stmt_booking);
}

if (isset($_POST['tbkd'])) { // Total Booked KiloMeter Details or Ticket Booked Details
    $bid = $_POST['bid'];
    $pdate = $_POST['date']; // Journey Date

    // Get fare from bus_details
    $sql_fare = "SELECT fare FROM bus_details WHERE bus_id=?";
    $stmt_fare = mysqli_prepare($con, $sql_fare);
    mysqli_stmt_bind_param($stmt_fare, "i", $bid);
    mysqli_stmt_execute($stmt_fare);
    $run_fare = mysqli_stmt_get_result($stmt_fare);
    $row_fare = mysqli_fetch_array($run_fare);
    mysqli_stmt_close($stmt_fare);

    $fare = $row_fare ? htmlspecialchars($row_fare['fare']) : 0;

    // Get ticket details for the specific bus and journey date
    $sql_tickets = "SELECT pname, seat_no, no_seat, ticket_status, jdate FROM ticket WHERE bus_id=? AND jdate=?";
    $stmt_tickets = mysqli_prepare($con, $sql_tickets);
    mysqli_stmt_bind_param($stmt_tickets, "is", $bid, $pdate);
    mysqli_stmt_execute($stmt_tickets);
    $run_tickets = mysqli_stmt_get_result($stmt_tickets);

    if (mysqli_num_rows($run_tickets) == 0) {
        echo "<h4 class='text-center text-white'>Result Not Found</h4>";
    } else {
        while ($row = mysqli_fetch_array($run_tickets)) {
            $pname = htmlspecialchars($row['pname']);
            $jdate = htmlspecialchars($row['jdate']);
            $seat_no = htmlspecialchars($row['seat_no']);
            $no_seat = htmlspecialchars($row['no_seat']);
            $status = htmlspecialchars($row['ticket_status']);
            $tfare = $fare * $no_seat; // Calculate total fare for this ticket

            echo " <div class='row'>
          <div class='col-md-2'>
            <h3 class='text-center text-white'> $pname</h3>
          </div>
          <div class='col-md-2'>
            <h3 class='text-center text-white'>$seat_no</h3>
          </div>
          <div class='col-md-2'>
            <h3 class='text-center text-white'>$no_seat</h3>
          </div>
          <div class='col-md-2'>
            <h3 class='text-center text-white'>$tfare</h3>
          </div>
          <div class='col-md-2'>
            <h3 class='text-center text-white'>$status</h3>
          </div>
          <div class='col-md-2'>
            <h3 class='text-center text-white'>$jdate</h3>
          </div>
        </div>";
        }
    }
    mysqli_stmt_close($stmt_tickets);
}

// Close the database connection at the very end of the script
mysqli_close($con);
?>