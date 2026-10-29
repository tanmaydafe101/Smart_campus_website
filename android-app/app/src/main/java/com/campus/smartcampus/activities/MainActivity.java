package com.campus.smartcampus.activities;

import android.content.Intent;
import android.os.Bundle;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.widget.Button;
import android.widget.TextView;
import android.widget.Toast;
import androidx.annotation.NonNull;
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

public class MainActivity extends AppCompatActivity {
    private TextView tvWelcomeName, tvTotalCount, tvOpenCount, tvResolvedCount, tvSeeAll, tvEmptyState;
    private Button btnNewComplaint, btnViewComplaints;
    private RecyclerView rvRecentComplaints;
    private SwipeRefreshLayout swipeRefresh;
    private SessionManager sessionManager;
    private TicketAdapter adapter;
    private List<Ticket> ticketList = new ArrayList<>();

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        sessionManager = new SessionManager(this);
        if (!sessionManager.isLoggedIn()) {
            startActivity(new Intent(this, LoginActivity.class));
            finish();
            return;
        }

        tvWelcomeName = findViewById(R.id.tvWelcomeName);
        tvTotalCount = findViewById(R.id.tvTotalCount);
        tvOpenCount = findViewById(R.id.tvOpenCount);
        tvResolvedCount = findViewById(R.id.tvResolvedCount);
        tvSeeAll = findViewById(R.id.tvSeeAll);
        tvEmptyState = findViewById(R.id.tvEmptyState);
        btnNewComplaint = findViewById(R.id.btnNewComplaint);
        btnViewComplaints = findViewById(R.id.btnViewComplaints);
        rvRecentComplaints = findViewById(R.id.rvRecentComplaints);
        swipeRefresh = findViewById(R.id.swipeRefresh);

        tvWelcomeName.setText("Hello, " + sessionManager.getUserName() + "!");

        rvRecentComplaints.setLayoutManager(new LinearLayoutManager(this));
        adapter = new TicketAdapter(this, ticketList, ticket -> {
            Intent intent = new Intent(MainActivity.this, ComplaintDetailsActivity.class);
            intent.putExtra("ticket_id", ticket.getId());
            intent.putExtra("ticket_code", ticket.getTicketCode());
            startActivity(intent);
        });
        rvRecentComplaints.setAdapter(adapter);

        btnNewComplaint.setOnClickListener(v -> startActivity(new Intent(MainActivity.this, NewComplaintActivity.class)));
        btnViewComplaints.setOnClickListener(v -> startActivity(new Intent(MainActivity.this, MyComplaintsActivity.class)));
        tvSeeAll.setOnClickListener(v -> startActivity(new Intent(MainActivity.this, MyComplaintsActivity.class)));

        swipeRefresh.setOnRefreshListener(this::loadDashboardData);
    }

    @Override
    protected void onResume() {
        super.onResume();
        loadDashboardData();
    }

    private void loadDashboardData() {
        swipeRefresh.setRefreshing(true);

        // Fetch User Complaints
        StringRequest stringRequest = new StringRequest(Request.Method.GET, ApiClient.MY_TICKETS_URL,
                response -> {
                    swipeRefresh.setRefreshing(false);
                    try {
                        JSONObject jsonObject = new JSONObject(response);
                        if (jsonObject.getBoolean("success")) {
                            JSONArray data = jsonObject.getJSONArray("data");
                            ticketList.clear();

                            int total = data.length();
                            int open = 0;
                            int resolved = 0;

                            for (int i = 0; i < data.length(); i++) {
                                JSONObject obj = data.getJSONObject(i);
                                String status = obj.getString("status");
                                if (status.equals("OPEN") || status.equals("ASSIGNED") || status.equals("IN_PROGRESS")) {
                                    open++;
                                } else if (status.equals("RESOLVED") || status.equals("CLOSED")) {
                                    resolved++;
                                }

                                if (i < 5) { // Only top 5 on dashboard
                                    ticketList.add(new Ticket(
                                            obj.getInt("id"),
                                            obj.getString("ticket_code"),
                                            obj.getString("title"),
                                            obj.getString("description"),
                                            obj.optString("photo_url", ""),
                                            obj.getString("priority"),
                                            status,
                                            obj.getString("category_name"),
                                            obj.getString("building") + " - " + obj.getString("room"),
                                            obj.optString("technician_name", "Unassigned"),
                                            obj.getString("created_at"),
                                            obj.optString("due_at", "")
                                    ));
                                }
                            }

                            tvTotalCount.setText(String.valueOf(total));
                            tvOpenCount.setText(String.valueOf(open));
                            tvResolvedCount.setText(String.valueOf(resolved));

                            adapter.notifyDataSetChanged();
                            tvEmptyState.setVisibility(ticketList.isEmpty() ? View.VISIBLE : View.GONE);
                        }
                    } catch (Exception e) {
                        Toast.makeText(MainActivity.this, "Error: " + e.getMessage(), Toast.LENGTH_SHORT).show();
                    }
                },
                error -> {
                    swipeRefresh.setRefreshing(false);
                    Toast.makeText(MainActivity.this, "Failed to load complaints.", Toast.LENGTH_SHORT).show();
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

    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        menu.add(0, 1, 0, "Profile");
        menu.add(0, 2, 1, "Sign Out");
        return true;
    }

    @Override
    public boolean onOptionsItemSelected(@NonNull MenuItem item) {
        if (item.getItemId() == 1) {
            startActivity(new Intent(MainActivity.this, ProfileActivity.class));
            return true;
        } else if (item.getItemId() == 2) {
            sessionManager.logoutUser();
            startActivity(new Intent(MainActivity.this, LoginActivity.class));
            finish();
            return true;
        }
        return super.onOptionsItemSelected(item);
    }
}
