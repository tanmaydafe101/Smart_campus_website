package com.campus.smartcampus.network;

public class ApiClient {
    // NETWORKING GUIDE FOR COLLEGE STUDENTS:
    // 1. Android Emulator: Use "http://10.0.2.2/SmartCampus/backend/"
    // 2. Physical Android Phone (connected to same Wi-Fi): Use your PC's LAN IP address, e.g. "http://10.61.168.88/SmartCampus/backend/"
    // DO NOT use "http://localhost/" inside Android because "localhost" on an Android device refers to the phone itself!
    
    public static final String BASE_URL = "http://10.61.168.88/SmartCampus/backend/";
    // Alternative for emulator:
    // public static final String BASE_URL = "http://10.0.2.2/SmartCampus/backend/";

    // Authentication Endpoints
    public static final String REGISTER_URL = BASE_URL + "api/auth/register.php";
    public static final String LOGIN_URL = BASE_URL + "api/auth/login.php";
    public static final String PROFILE_URL = BASE_URL + "api/auth/profile.php";

    // Campus Facility Endpoints
    public static final String CATEGORIES_URL = BASE_URL + "api/categories/list.php";
    public static final String LOCATIONS_URL = BASE_URL + "api/locations/list.php";
    public static final String VERIFY_QR_URL = BASE_URL + "api/locations/verify_qr.php?token=";

    // Complaints Endpoints
    public static final String CREATE_TICKET_URL = BASE_URL + "api/tickets/create.php";
    public static final String MY_TICKETS_URL = BASE_URL + "api/tickets/list_user.php";
    public static final String TICKET_DETAILS_URL = BASE_URL + "api/tickets/details.php?id=";
    public static final String ADD_COMMENT_URL = BASE_URL + "api/tickets/add_comment.php";
}
