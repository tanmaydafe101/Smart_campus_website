package com.campus.smartcampus.activities;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.TextView;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import com.android.volley.AuthFailureError;
import com.android.volley.Request;
import com.android.volley.toolbox.StringRequest;
import com.campus.smartcampus.R;
import com.campus.smartcampus.adapters.TicketAdapter;
import com.campus.smartcampus.models.Ticket;
import com.campus.smartcampus.network.ApiClient;
import com.campus.smartcampus.network.VolleySingleton;
import com.campus.smartcampus.utils.SessionManager;

import org.json.JSONArray;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class MyComplaintsActivity extends AppCompatActivity {
    private RecyclerView rvComplaints;
    private SwipeRefreshLayout swipeRefresh;
    private TextView tvEmpty;
    private Button btnFilterAll, btnFilterOpen, btnFilterProgress, btnFilterResolved;
    private SessionManager sessionManager;
    private TicketAdapter adapter;
    private List<Ticket> fullList = new ArrayList<>();
    private List<Ticket> displayList = new ArrayList<>();
    private String currentFilter = "ALL";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_my_complaints);
        setTitle("My Complaints");

        sessionManager = new SessionManager(this);

        rvComplaints = findViewById(R.id.rvComplaints);
        swipeRefresh = findViewById(R.id.swipeRefresh);
        tvEmpty = findViewById(R.id.tvEmpty);
        btnFilterAll = findViewById(R.id.btnFilterAll);
        btnFilterOpen = findViewById(R.id.btnFilterOpen);
        btnFilterProgress = findViewById(R.id.btnFilterProgress);
        btnFilterResolved = findViewById(R.id.btnFilterResolved);

        rvComplaints.setLayoutManager(new LinearLayoutManager(this));
        adapter = new TicketAdapter(this, displayList, ticket -> {
            Intent intent = new Intent(MyComplaintsActivity.this, ComplaintDetailsActivity.class);
            intent.putExtra("ticket_id", ticket.getId());
            intent.putExtra("ticket_code", ticket.getTicketCode());
            startActivity(intent);
        });
        rvComplaints.setAdapter(adapter);

        btnFilterAll.setOnClickListener(v -> setFilter("ALL"));
        btnFilterOpen.setOnClickListener(v -> setFilter("OPEN"));
        btnFilterProgress.setOnClickListener(v -> setFilter("IN_PROGRESS"));
        btnFilterResolved.setOnClickListener(v -> setFilter("RESOLVED"));

        swipeRefresh.setOnRefreshListener(this::fetchComplaints);
    }

    @Override
    protected void onResume() {
        super.onResume();
        fetchComplaints();
    }

    private void setFilter(String filter) {
        this.currentFilter = filter;
        applyFilter();
    }

    private void applyFilter() {
        displayList.clear();
        for (Ticket t : fullList) {
            if (currentFilter.equals("ALL")) {
                displayList.add(t);
            } else if (currentFilter.equals("OPEN") && (t.getStatus().equals("OPEN") || t.getStatus().equals("ASSIGNED"))) {
                displayList.add(t);
            } else if (currentFilter.equals("IN_PROGRESS") && t.getStatus().equals("IN_PROGRESS")) {
                displayList.add(t);
            } else if (currentFilter.equals("RESOLVED") && (t.getStatus().equals("RESOLVED") || t.getStatus().equals("CLOSED"))) {
                displayList.add(t);
            }
        }
        adapter.notifyDataSetChanged();
        tvEmpty.setVisibility(displayList.isEmpty() ? View.VISIBLE : View.GONE);
    }

    private void fetchComplaints() {
        swipeRefresh.setRefreshing(true);

        StringRequest stringRequest = new StringRequest(Request.Method.GET, ApiClient.MY_TICKETS_URL,
                response -> {
                    swipeRefresh.setRefreshing(false);
                    try {
                        JSONObject jsonObject = new JSONObject(response);
                        if (jsonObject.getBoolean("success")) {
                            JSONArray data = jsonObject.getJSONArray("data");
                            fullList.clear();

                            for (int i = 0; i < data.length(); i++) {
                                JSONObject obj = data.getJSONObject(i);
                                fullList.add(new Ticket(
                                        obj.getInt("id"),
                                        obj.getString("ticket_code"),
                                        obj.getString("title"),
                                        obj.getString("description"),
                                        obj.optString("photo_url", ""),
                                        obj.getString("priority"),
                                        obj.getString("status"),
                                        obj.getString("category_name"),
                                        obj.getString("building") + " - " + obj.getString("room"),
                                        obj.optString("technician_name", "Unassigned"),
                                        obj.getString("created_at"),
                                        obj.optString("due_at", "")
                                ));
                            }
                            applyFilter();
                        }
                    } catch (Exception e) {
                        Toast.makeText(MyComplaintsActivity.this, "Error: " + e.getMessage(), Toast.LENGTH_SHORT).show();
                    }
                },
                error -> {
                    swipeRefresh.setRefreshing(false);
                    Toast.makeText(MyComplaintsActivity.this, "Failed to load complaints.", Toast.LENGTH_SHORT).show();
                }) {
            @Override
            public Map<String, String> getHeaders() throws AuthFailureError {
                Map<String, String> headers = new HashMap<>();
                headers.put("Authorization", "Bearer " + sessionManager.getToken());
                return headers;
            }
        };

        VolleySingleton.getInstance(this).addToRequestQueue(stringRequest);
    }
}
