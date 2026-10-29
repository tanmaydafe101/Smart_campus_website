package com.campus.smartcampus.activities;

import android.os.Bundle;
import android.text.TextUtils;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.ContextCompat;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.android.volley.AuthFailureError;
import com.android.volley.Request;
import com.android.volley.toolbox.StringRequest;
import com.bumptech.glide.Glide;
import com.campus.smartcampus.R;
import com.campus.smartcampus.adapters.CommentAdapter;
import com.campus.smartcampus.models.Comment;
import com.campus.smartcampus.network.ApiClient;
import com.campus.smartcampus.network.VolleySingleton;
import com.campus.smartcampus.utils.SessionManager;

import org.json.JSONArray;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class ComplaintDetailsActivity extends AppCompatActivity {
    private TextView tvDetailCode, tvDetailStatus, tvDetailTitle, tvDetailMeta, tvDetailTechnician, tvDetailDescription, tvNoComments;
    private ImageView ivDetailPhoto;
    private RecyclerView rvComments;
    private EditText etNewComment;
    private Button btnSendComment;

    private SessionManager sessionManager;
    private int ticketId;
    private List<Comment> commentList = new ArrayList<>();
    private CommentAdapter commentAdapter;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_complaint_details);

        ticketId = getIntent().getIntExtra("ticket_id", 0);
        String ticketCode = getIntent().getStringExtra("ticket_code");
        setTitle(ticketCode != null ? ticketCode : "Complaint Details");

        sessionManager = new SessionManager(this);

        tvDetailCode = findViewById(R.id.tvDetailCode);
        tvDetailStatus = findViewById(R.id.tvDetailStatus);
        tvDetailTitle = findViewById(R.id.tvDetailTitle);
        tvDetailMeta = findViewById(R.id.tvDetailMeta);
        tvDetailTechnician = findViewById(R.id.tvDetailTechnician);
        tvDetailDescription = findViewById(R.id.tvDetailDescription);
        tvNoComments = findViewById(R.id.tvNoComments);
        ivDetailPhoto = findViewById(R.id.ivDetailPhoto);
        rvComments = findViewById(R.id.rvComments);
        etNewComment = findViewById(R.id.etNewComment);
        btnSendComment = findViewById(R.id.btnSendComment);

        rvComments.setLayoutManager(new LinearLayoutManager(this));
        commentAdapter = new CommentAdapter(this, commentList);
        rvComments.setAdapter(commentAdapter);

        btnSendComment.setOnClickListener(v -> postComment());

        loadTicketDetails();
    }

    private void loadTicketDetails() {
        String url = ApiClient.TICKET_DETAILS_URL + ticketId;

        StringRequest stringRequest = new StringRequest(Request.Method.GET, url,
                response -> {
                    try {
                        JSONObject jsonObject = new JSONObject(response);
                        if (jsonObject.getBoolean("success")) {
                            JSONObject data = jsonObject.getJSONObject("data");
                            JSONObject ticket = data.getJSONObject("ticket");

                            tvDetailCode.setText(ticket.getString("ticket_code"));
                            tvDetailTitle.setText(ticket.getString("title"));
                            tvDetailDescription.setText(ticket.getString("description"));
                            tvDetailMeta.setText("Category: " + ticket.getString("category_name") + "\nLocation: " +
                                    ticket.getString("building") + " - " + ticket.getString("room"));

                            String tech = ticket.optString("technician_name", "None Assigned");
                            tvDetailTechnician.setText("Technician: " + (tech.isEmpty() || tech.equals("null") ? "Unassigned" : tech));

                            String status = ticket.getString("status");
                            tvDetailStatus.setText(status.replace("_", " "));
                            updateStatusBadgeColor(status);

                            // Load photo if available
                            String photoUrl = ticket.optString("photo_url", "");
                            if (!TextUtils.isEmpty(photoUrl) && !photoUrl.equals("null")) {
                                ivDetailPhoto.setVisibility(View.VISIBLE);
                                Glide.with(ComplaintDetailsActivity.this)
                                        .load(photoUrl)
                                        .into(ivDetailPhoto);
                            } else {
                                ivDetailPhoto.setVisibility(View.GONE);
                            }

                            // Load Comments
                            JSONArray comments = data.getJSONArray("comments");
                            commentList.clear();
                            for (int i = 0; i < comments.length(); i++) {
                                JSONObject c = comments.getJSONObject(i);
                                commentList.add(new Comment(
                                        c.getInt("id"),
                                        c.getString("author_name"),
                                        c.getString("author_role"),
                                        c.getString("message"),
                                        c.getString("created_at")
                                ));
                            }
                            commentAdapter.notifyDataSetChanged();
                            tvNoComments.setVisibility(commentList.isEmpty() ? View.VISIBLE : View.GONE);
                        }
                    } catch (Exception e) {
                        Toast.makeText(ComplaintDetailsActivity.this, "Error: " + e.getMessage(), Toast.LENGTH_SHORT).show();
                    }
                },
                error -> Toast.makeText(ComplaintDetailsActivity.this, "Failed to load ticket details", Toast.LENGTH_SHORT).show()) {
            @Override
            public Map<String, String> getHeaders() throws AuthFailureError {
                Map<String, String> headers = new HashMap<>();
                headers.put("Authorization", "Bearer " + sessionManager.getToken());
                return headers;
            }
        };

        VolleySingleton.getInstance(this).addToRequestQueue(stringRequest);
    }

    private void postComment() {
        String msg = etNewComment.getText().toString().trim();
        if (TextUtils.isEmpty(msg)) {
            return;
        }

        btnSendComment.setEnabled(false);

        StringRequest stringRequest = new StringRequest(Request.Method.POST, ApiClient.ADD_COMMENT_URL,
                response -> {
                    btnSendComment.setEnabled(true);
                    etNewComment.setText("");
                    loadTicketDetails(); // Refresh comments list
                },
                error -> {
                    btnSendComment.setEnabled(true);
                    Toast.makeText(ComplaintDetailsActivity.this, "Failed to post comment", Toast.LENGTH_SHORT).show();
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
                params.put("ticket_id", String.valueOf(ticketId));
                params.put("message", msg);
                return params;
            }
        };

        VolleySingleton.getInstance(this).addToRequestQueue(stringRequest);
    }

    private void updateStatusBadgeColor(String status) {
        switch (status) {
            case "OPEN":
                tvDetailStatus.setBackgroundColor(ContextCompat.getColor(this, R.color.status_open_bg));
                tvDetailStatus.setTextColor(ContextCompat.getColor(this, R.color.status_open));
                break;
            case "ASSIGNED":
                tvDetailStatus.setBackgroundColor(ContextCompat.getColor(this, R.color.status_assigned_bg));
                tvDetailStatus.setTextColor(ContextCompat.getColor(this, R.color.status_assigned));
                break;
            case "IN_PROGRESS":
                tvDetailStatus.setBackgroundColor(ContextCompat.getColor(this, R.color.status_in_progress_bg));
                tvDetailStatus.setTextColor(ContextCompat.getColor(this, R.color.status_in_progress));
                break;
            case "RESOLVED":
                tvDetailStatus.setBackgroundColor(ContextCompat.getColor(this, R.color.status_resolved_bg));
                tvDetailStatus.setTextColor(ContextCompat.getColor(this, R.color.status_resolved));
                break;
            default:
                tvDetailStatus.setBackgroundColor(ContextCompat.getColor(this, R.color.status_closed_bg));
                tvDetailStatus.setTextColor(ContextCompat.getColor(this, R.color.status_closed));
                break;
        }
    }
}
