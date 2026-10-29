package com.campus.smartcampus.activities;

import android.content.Intent;
import android.os.Bundle;
import android.widget.Button;
import android.widget.TextView;
import androidx.appcompat.app.AppCompatActivity;

import com.campus.smartcampus.R;
import com.campus.smartcampus.utils.SessionManager;

public class ProfileActivity extends AppCompatActivity {
    private TextView tvProfileInitials, tvProfileName, tvProfileEmail, tvProfileRole;
    private Button btnLogout;
    private SessionManager sessionManager;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_profile);
        setTitle("Student Profile");

        sessionManager = new SessionManager(this);

        tvProfileInitials = findViewById(R.id.tvProfileInitials);
        tvProfileName = findViewById(R.id.tvProfileName);
        tvProfileEmail = findViewById(R.id.tvProfileEmail);
        tvProfileRole = findViewById(R.id.tvProfileRole);
        btnLogout = findViewById(R.id.btnLogout);

        String name = sessionManager.getUserName();
        tvProfileName.setText(name);
        tvProfileEmail.setText(sessionManager.getUserEmail());
        tvProfileRole.setText(sessionManager.getUserRole().toUpperCase());

        if (name != null && name.length() > 0) {
            String[] parts = name.split(" ");
            if (parts.length >= 2) {
                tvProfileInitials.setText(("" + parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase());
            } else {
                tvProfileInitials.setText(name.substring(0, Math.min(2, name.length())).toUpperCase());
            }
        }

        btnLogout.setOnClickListener(v -> {
            sessionManager.logoutUser();
            Intent intent = new Intent(ProfileActivity.this, LoginActivity.class);
            intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
            startActivity(intent);
            finish();
        });
    }
}
