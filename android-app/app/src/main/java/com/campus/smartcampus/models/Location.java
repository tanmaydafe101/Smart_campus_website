package com.campus.smartcampus.models;

public class Location {
    private int id;
    private String building;
    private String room;
    private String qrCodeToken;

    public Location(int id, String building, String room, String qrCodeToken) {
        this.id = id;
        this.building = building;
        this.room = room;
        this.qrCodeToken = qrCodeToken;
    }

    public int getId() { return id; }
    public String getBuilding() { return building; }
    public String getRoom() { return room; }
    public String getQrCodeToken() { return qrCodeToken; }

    @Override
    public String toString() {
        return building + " - " + room; // Displayed in Spinner
    }
}
