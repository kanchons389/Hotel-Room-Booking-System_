<?php

require_once __DIR__ . "/../config/database.php";


function getGuestBillingHistory(
    $guestId,
    $search = "",
    $status = "",
    $fromDate = "",
    $toDate = ""
) {
    global $conn;

    $sql = "
        SELECT
            billing.id,

            b.id AS booking_number,

            rt.name AS room_type_name,

            r.room_number,

            billing.base_amount,
            billing.extras_amount,
            billing.discount_amount,
            billing.total_amount,

            billing.payment_method,
            billing.payment_status

        FROM billing

        INNER JOIN bookings b
            ON b.id = billing.booking_id

        INNER JOIN room_types rt
            ON rt.id = b.room_type_id

        LEFT JOIN rooms r
            ON r.id = b.room_id

        WHERE b.guest_id = ?
    ";

    $params = [$guestId];
    $types = "i";


    // Search
    if (!empty($search)) {

        $sql .= "
            AND (
                b.id LIKE ?
                OR rt.name LIKE ?
                OR r.room_number LIKE ?
                OR billing.payment_method LIKE ?
            )
        ";

        $searchValue = "%" . $search . "%";

        $params[] = $searchValue;
        $params[] = $searchValue;
        $params[] = $searchValue;
        $params[] = $searchValue;

        $types .= "ssss";
    }


    // Payment status
    if (!empty($status)) {

        $sql .= " AND billing.payment_status = ?";

        $params[] = $status;
        $types .= "s";
    }


    // From date
    if (!empty($fromDate)) {

        $sql .= " AND b.checkin_date >= ?";

        $params[] = $fromDate;
        $types .= "s";
    }


    // To date
    if (!empty($toDate)) {

        $sql .= " AND b.checkout_date <= ?";

        $params[] = $toDate;
        $types .= "s";
    }


    $sql .= " ORDER BY billing.id DESC";


    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Billing history query failed: " . $conn->error);
    }

    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        die("Billing history execute failed: " . $stmt->error);
    }

    return $stmt->get_result();
}


function getGuestReceiptDetails($guestId, $billingId)
{
    global $conn;

    $guestId = intval($guestId);
    $billingId = intval($billingId);

    $sql = "
        SELECT
            billing.id,
            billing.base_amount,
            billing.extras_amount,
            billing.discount_amount,
            billing.total_amount,
            billing.payment_method,
            billing.payment_status,
            billing.paid_at,

            b.id AS booking_number,
            b.checkin_date,
            b.checkout_date,
            b.num_guests,

            u.name AS guest_name,
            u.email AS guest_email,
            u.phone AS guest_phone,
            u.nationality,
            u.id_number,

            rt.name AS room_type_name,
            r.room_number

        FROM billing

        INNER JOIN bookings b
            ON b.id = billing.booking_id

        INNER JOIN users u
            ON u.id = b.guest_id

        INNER JOIN room_types rt
            ON rt.id = b.room_type_id

        LEFT JOIN rooms r
            ON r.id = b.room_id

        WHERE billing.id = ?
        AND b.guest_id = ?

        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Get guest receipt failed: " . $conn->error);
    }

    $stmt->bind_param("ii", $billingId, $guestId);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

function getAllPricing()
{
    global $conn;

    $sql = "SELECT 
                seasonal_pricing.id,
                seasonal_pricing.label,
                seasonal_pricing.start_date,
                seasonal_pricing.end_date,
                seasonal_pricing.price_per_night,
                seasonal_pricing.is_active,
                room_types.name AS room_type
            FROM seasonal_pricing
            INNER JOIN room_types
            ON seasonal_pricing.room_type_id = room_types.id
            ORDER BY seasonal_pricing.id DESC";

    return $conn->query($sql);
}

function getPricingById($id)
{
    global $conn;

    $sql = "SELECT * FROM seasonal_pricing WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

function addPricing($roomTypeId, $label, $startDate, $endDate, $price, $status)
{
    global $conn;

    $sql = "INSERT INTO seasonal_pricing
            (room_type_id, label, start_date, end_date, price_per_night, is_active)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssdi", $roomTypeId, $label, $startDate, $endDate, $price, $status);

    try {
        return $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        return false;
    }
}

function updatePricing($id, $roomTypeId, $label, $startDate, $endDate, $price, $status)
{
    global $conn;

    $sql = "UPDATE seasonal_pricing
            SET room_type_id = ?, label = ?, start_date = ?, end_date = ?, price_per_night = ?, is_active = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssdii", $roomTypeId, $label, $startDate, $endDate, $price, $status, $id);

    try {
        return $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        return false;
    }
}

function deletePricing($id)
{
    global $conn;

    $sql = "DELETE FROM seasonal_pricing WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    try {
        return $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        return false;
    }
}

?>