package com.campus.smartcampus.activities;

import android.graphics.Bitmap;
import android.net.Uri;
import android.os.Bundle;
import android.provider.MediaStore;
import android.text.TextUtils;
import android.util.Base64;
import android.view.View;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.ImageView;
import android.widget.ProgressBar;
import android.widget.Spinner;
import android.widget.TextView;
import android.widget.Toast;
import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.appcompat.app.AppCompatActivity;

import com.android.volley.AuthFailureError;
import com.android.volley.Request;
import com.android.volley.toolbox.StringRequest;
import com.campus.smartcampus.R;
import com.campus.smartcampus.models.Category;
import com.campus.smartcampus.models.Location;
import com.campus.smartcampus.network.ApiClient;
import com.campus.smartcampus.network.VolleySingleton;
import com.campus.smartcampus.utils.SessionManager;
import com.google.android.material.textfield.TextInputEditText;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class NewComplaintActivity extends AppCompatActivity {
    private TextInputEditText etTitle, etDescription;
    private Spinner spCategory, spLocation, spPriority;
    private Button btnSelectPhoto, btnSubmit;
    private TextView tvSelectedPhotoName;
    private ImageView ivPreview;
    private ProgressBar progressBar;

    private SessionManager sessionManager;
    private List<Category> categoryList = new ArrayList<>();
    private List<Location> locationList = new ArrayList<>();
    private Bitmap selectedBitmap = null;

    private final ActivityResultLauncher<String> photoPickerLauncher =
            registerForActivityResult(new ActivityResultContracts.GetContent(), uri -> {
                if (uri != null) {
                    try {
                        selectedBitmap = MediaStore.Images.Media.getBitmap(getContentResolver(), uri);
                        ivPreview.setImageBitmap(selectedBitmap);
                        ivPreview.setVisibility(View.VISIBLE);
                        tvSelectedPhotoName.setText("Photo attached");
                    } catch (IOException e) {
                        Toast.makeText(this, "Failed to load image", Toast.LENGTH_SHORT).show();
                    }
                }
            });

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_new_complaint);
        setTitle("Report New Issue");

        sessionManager = new SessionManager(this);

        etTitle = findViewById(R.id.etTitle);
        etDescription = findViewById(R.id.etDescription);
        spCategory = findViewById(R.id.spCategory);
        spLocation = findViewById(R.id.spLocation);
        spPriority = findViewById(R.id.spPriority);
        btnSelectPhoto = findViewById(R.id.btnSelectPhoto);
        btnSubmit = findViewById(R.id.btnSubmit);
        tvSelectedPhotoName = findViewById(R.id.tvSelectedPhotoName);
        ivPreview = findViewById(R.id.ivPreview);
        progressBar = findViewById(R.id.progressBar);

        // Priority Spinner options
        String[] priorities = {"LOW", "MEDIUM", "HIGH", "CRITICAL"};
        ArrayAdapter<String> priorityAdapter = new ArrayAdapter<>(this, android.R.layout.simple_spinner_dropdown_item, priorities);
        spPriority.setAdapter(priorityAdapter);
        spPriority.setSelection(1); // Default MEDIUM

        btnSelectPhoto.setOnClickListener(v -> photoPickerLauncher.launch("image/*"));
        btnSubmit.setOnClickListener(v -> submitComplaint());

        loadCategories();
        loadLocations();
    }

    private void loadCategories() {
        StringRequest stringRequest = new StringRequest(Request.Method.GET, ApiClient.CATEGORIES_URL,
                response -> {
                    try {
                        JSONObject jsonObject = new JSONObject(response);
                        if (jsonObject.getBoolean("success")) {
                            JSONArray data = jsonObject.getJSONArray("data");
                            categoryList.clear();
                            for (int i = 0; i < data.length(); i++) {
                                JSONObject obj = data.getJSONObject(i);
                                categoryList.add(new Category(
                                        obj.getInt("id"),
                                        obj.getString("name"),
                                        obj.optString("description", ""),
                                        obj.optString("default_priority", "MEDIUM")
                                ));
                            }
                            ArrayAdapter<Category> adapter = new ArrayAdapter<>(this, android.R.layout.simple_spinner_dropdown_item, categoryList);
                            spCategory.setAdapter(adapter);
                        }
                    } catch (Exception ignored) {}
                }, null);
        VolleySingleton.getInstance(this).addToRequestQueue(stringRequest);
    }

    private void loadLocations() {
        StringRequest stringRequest = new StringRequest(Request.Method.GET, ApiClient.LOCATIONS_URL,
                response -> {
                    try {
                        JSONObject jsonObject = new JSONObject(response);
                        if (jsonObject.getBoolean("success")) {
                            JSONArray data = jsonObject.getJSONArray("data");
                            locationList.clear();
                            for (int i = 0; i < data.length(); i++) {
                                JSONObject obj = data.getJSONObject(i);
                                locationList.add(new Location(
                                        obj.getInt("id"),
                                        obj.getString("building"),
                                        obj.getString("room"),
                                        obj.getString("qr_code_token")
                                ));
                            }
                            ArrayAdapter<Location> adapter = new ArrayAdapter<>(this, android.R.layout.simple_spinner_dropdown_item, locationList);
                            spLocation.setAdapter(adapter);
                        }
                    } catch (Exception ignored) {}
                }, null);
        VolleySingleton.getInstance(this).addToRequestQueue(stringRequest);
    }

    private void submitComplaint() {
        String title = etTitle.getText() != null ? etTitle.getText().toString().trim() : "";
        String description = etDescription.getText() != null ? etDescription.getText().toString().trim() : "";

        if (TextUtils.isEmpty(title)) {
            etTitle.setError("Title is required");
            return;
        }

        if (TextUtils.isEmpty(description)) {
            etDescription.setError("Description is required");
            return;
        }

        if (categoryList.isEmpty() || locationList.isEmpty()) {
            Toast.makeText(this, "Categories or locations still loading...", Toast.LENGTH_SHORT).show();
            return;
        }

        Category selectedCategory = (Category) spCategory.getSelectedItem();
        Location selectedLocation = (Location) spLocation.getSelectedItem();
        String priority = spPriority.getSelectedItem().toString();

        progressBar.setVisibility(View.VISIBLE);
        btnSubmit.setEnabled(false);

        StringRequest stringRequest = new StringRequest(Request.Method.POST, ApiClient.CREATE_TICKET_URL,
                response -> {
                    progressBar.setVisibility(View.GONE);
                    btnSubmit.setEnabled(true);
                    try {
                        JSONObject jsonObject = new JSONObject(response);
                        if (jsonObject.getBoolean("success")) {
                            String message = jsonObject.getString("message");
                            Toast.makeText(NewComplaintActivity.this, message, Toast.LENGTH_LONG).show();
                            finish();
                        } else {
                            Toast.makeText(NewComplaintActivity.this, jsonObject.getString("message"), Toast.LENGTH_LONG).show();
                        }
                    } catch (Exception e) {
                        Toast.makeText(NewComplaintActivity.this, "Error: " + e.getMessage(), Toast.LENGTH_LONG).show();
                    }
                },
                error -> {
                    progressBar.setVisibility(View.GONE);
                    btnSubmit.setEnabled(true);
                    Toast.makeText(NewComplaintActivity.this, "Failed to submit complaint. Check network.", Toast.LENGTH_SHORT).show();
                }) {
            @Override
            public Map<String, String> getHeaders() throws AuthFailureError {
                Map<String, String> headers = new HashMap<>();
                headers.put("Authorization", "Bearer " + sessionManager.getToken());
                return headers;
            }

            @Override
            protected Map<String, String> getParams() {
                Map<String, String> params = new HashMap<>();
                params.put("title", title);
                params.put("description", description);
                params.put("category_id", String.valueOf(selectedCategory.getId()));
                params.put("location_id", String.valueOf(selectedLocation.getId()));
                params.put("priority", priority);
                return params;
            }
        };

        VolleySingleton.getInstance(this).addToRequestQueue(stringRequest);
    }
}
