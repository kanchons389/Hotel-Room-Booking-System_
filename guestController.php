<?php

session_start();

require_once __DIR__ . "/../models/userModel.php";
require_once __DIR__ . "/../models/roomModel.php";
require_once __DIR__ . "/../models/bookingModel.php";
require_once __DIR__ . "/../models/pricingModel.php";


if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "guest") {
    header("Location: ../views/auth/login.php");
    exit();
}



if ($_POST["action"] == "search_rooms") {

    $checkin = trim($_POST["checkin"]);

    $checkout = trim($_POST["checkout"]);

    $guests = intval($_POST["guests"]);

    $rooms = searchAvailableRoomTypes(
        $checkin,
        $checkout,
        $guests
    );

    if ($rooms->num_rows == 0) {

        echo "
            <div class='empty-state'>
                No available rooms found.
            </div>
        ";

        exit();
    }

    echo "<div class='room-grid'>";

    while ($row = $rooms->fetch_assoc()) {

        echo "

        

            <div class='room-content'>

                <h3>
                    " . htmlspecialchars($row["name"]) . "
                </h3>

                <p>
                    " . htmlspecialchars(substr($row["description"],0,120)) . "...
                </p>

                <div class='room-meta'>

                    <span>
                        Max Guests:
                        " . $row["max_capacity"] . "
                    </span>

                    <span>
                        Available:
                        " . $row["available_rooms"] . "
                    </span>

                </div>

                <div class='room-price'>
                    ৳ " . number_format($row["price_per_night"],2) . "
                    <small>/ night</small>
                </div>

                <a class='room-btn'

                href='../guest/room_details.php?id=" . $row["id"] . "&checkin=" . $checkin . "&checkout=" . $checkout . "&guests=" . $guests . "'>

                    View Details

                </a>

            </div>

        </div>
        ";
    }

    echo "</div>";

    exit();
}

if ($_POST["action"] == "create_booking") {

    $guestId = $_SESSION["user_id"];

    $roomTypeId = intval($_POST["room_type_id"]);
    $checkin = $_POST["checkin"];
    $checkout = $_POST["checkout"];
    $guests = intval($_POST["guests"]);

    if (strtotime($checkin) === false || strtotime($checkout) === false || strtotime($checkout) <= strtotime($checkin)) {
        header("Location: ../views/guest/search_rooms.php?error=invalid_dates");
        exit();
    }

    $priceData = calculateGuestBookingPrice($roomTypeId, $checkin, $checkout);

    if (!$priceData) {
        header("Location: ../views/guest/search_rooms.php?error=booking_failed");
        exit();
    }

    $bookingId = createGuestBookingWithBilling(
        $guestId,
        $roomTypeId,
        $checkin,
        $checkout,
        $guests,
        $priceData["final_amount"],
        $priceData["base_amount"],
        $priceData["discount"]
    );

    if ($bookingId) {
        header("Location: ../views/guest/booking_confirmation.php?id=" . $bookingId);
        exit();
    }

    header("Location: ../views/guest/search_rooms.php?error=booking_failed");
    exit();
}

if ($_POST["action"] == "cancel_booking") {

    $guestId = $_SESSION["user_id"];
    $bookingId = intval($_POST["booking_id"]);

    $result = cancelGuestBooking($guestId, $bookingId);

    header("Location: ../views/guest/booking_details.php?id=" . $bookingId . "&cancel=" . $result);
    exit();
}

if ($_POST["action"] == "request_modification") {

    $guestId = $_SESSION["user_id"];

    $bookingId = intval($_POST["booking_id"]);
    $newCheckin = $_POST["new_checkin_date"];
    $newCheckout = $_POST["new_checkout_date"];
    $reason = trim($_POST["reason"]);

    createBookingModificationRequest($guestId, $bookingId, $newCheckin, $newCheckout, $reason);

    header("Location: ../views/guest/modification_request.php?success=1");
    exit();
}

?>