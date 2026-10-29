package com.campus.smartcampus.models;

public class Comment {
    private int id;
    private String authorName;
    private String authorRole;
    private String message;
    private String createdAt;

    public Comment(int id, String authorName, String authorRole, String message, String createdAt) {
        this.id = id;
        this.authorName = authorName;
        this.authorRole = authorRole;
        this.message = message;
        this.createdAt = createdAt;
    }

    public int getId() { return id; }
    public String getAuthorName() { return authorName; }
    public String getAuthorRole() { return authorRole; }
    public String getMessage() { return message; }
    public String getCreatedAt() { return createdAt; }
}
