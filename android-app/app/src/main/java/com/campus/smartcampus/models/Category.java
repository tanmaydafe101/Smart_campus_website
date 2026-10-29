package com.campus.smartcampus.models;

public class Category {
    private int id;
    private String name;
    private String description;
    private String defaultPriority;

    public Category(int id, String name, String description, String defaultPriority) {
        this.id = id;
        this.name = name;
        this.description = description;
        this.defaultPriority = defaultPriority;
    }

    public int getId() { return id; }
    public String getName() { return name; }
    public String getDescription() { return description; }
    public String getDefaultPriority() { return defaultPriority; }

    @Override
    public String toString() {
        return name; // Useful for Android Spinner dropdowns
    }
}
