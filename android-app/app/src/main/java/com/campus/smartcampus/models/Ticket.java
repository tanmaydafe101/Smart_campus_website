package com.campus.smartcampus.models;

import java.io.Serializable;

public class Ticket implements Serializable {
    private int id;
    private String ticketCode;
    private String title;
    private String description;
    private String photoUrl;
    private String priority;
    private String status;
    private String categoryName;
    private String location;
    private String technicianName;
    private String createdAt;
    private String dueAt;

    public Ticket(int id, String ticketCode, String title, String description, String photoUrl,
                  String priority, String status, String categoryName, String location,
                  String technicianName, String createdAt, String dueAt) {
        this.id = id;
        this.ticketCode = ticketCode;
        this.title = title;
        this.description = description;
        this.photoUrl = photoUrl;
        this.priority = priority;
        this.status = status;
        this.categoryName = categoryName;
        this.location = location;
        this.technicianName = technicianName;
        this.createdAt = createdAt;
        this.dueAt = dueAt;
    }

    public int getId() { return id; }
    public String getTicketCode() { return ticketCode; }
    public String getTitle() { return title; }
    public String getDescription() { return description; }
    public String getPhotoUrl() { return photoUrl; }
    public String getPriority() { return priority; }
    public String getStatus() { return status; }
    public String getCategoryName() { return categoryName; }
    public String getLocation() { return location; }
    public String getTechnicianName() { return technicianName; }
    public String getCreatedAt() { return createdAt; }
    public String getDueAt() { return dueAt; }
}
