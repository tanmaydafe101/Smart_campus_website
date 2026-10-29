package com.campus.smartcampus.adapters;

import android.content.Context;
import android.graphics.Color;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.core.content.ContextCompat;
import androidx.recyclerview.widget.RecyclerView;
import com.campus.smartcampus.R;
import com.campus.smartcampus.models.Ticket;

import java.util.List;

public class TicketAdapter extends RecyclerView.Adapter<TicketAdapter.TicketViewHolder> {
    private Context context;
    private List<Ticket> ticketList;
    private OnTicketClickListener listener;

    public interface OnTicketClickListener {
        void onTicketClick(Ticket ticket);
    }

    public TicketAdapter(Context context, List<Ticket> ticketList, OnTicketClickListener listener) {
        this.context = context;
        this.ticketList = ticketList;
        this.listener = listener;
    }

    @NonNull
    @Override
    public TicketViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.item_ticket, parent, false);
        return new TicketViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull TicketViewHolder holder, int position) {
        Ticket ticket = ticketList.get(position);
        holder.tvTicketCode.setText(ticket.getTicketCode());
        holder.tvTitle.setText(ticket.getTitle());
        holder.tvCategoryAndLocation.setText(ticket.getCategoryName() + " • " + ticket.getLocation());
        holder.tvPriority.setText("Priority: " + ticket.getPriority());
        holder.tvDate.setText(ticket.getCreatedAt());

        // Status Badge Style
        holder.tvStatusBadge.setText(ticket.getStatus().replace("_", " "));
        switch (ticket.getStatus()) {
            case "OPEN":
                holder.tvStatusBadge.setBackgroundColor(ContextCompat.getColor(context, R.color.status_open_bg));
                holder.tvStatusBadge.setTextColor(ContextCompat.getColor(context, R.color.status_open));
                break;
            case "ASSIGNED":
                holder.tvStatusBadge.setBackgroundColor(ContextCompat.getColor(context, R.color.status_assigned_bg));
                holder.tvStatusBadge.setTextColor(ContextCompat.getColor(context, R.color.status_assigned));
                break;
            case "IN_PROGRESS":
                holder.tvStatusBadge.setBackgroundColor(ContextCompat.getColor(context, R.color.status_in_progress_bg));
                holder.tvStatusBadge.setTextColor(ContextCompat.getColor(context, R.color.status_in_progress));
                break;
            case "RESOLVED":
                holder.tvStatusBadge.setBackgroundColor(ContextCompat.getColor(context, R.color.status_resolved_bg));
                holder.tvStatusBadge.setTextColor(ContextCompat.getColor(context, R.color.status_resolved));
                break;
            case "CLOSED":
            default:
                holder.tvStatusBadge.setBackgroundColor(ContextCompat.getColor(context, R.color.status_closed_bg));
                holder.tvStatusBadge.setTextColor(ContextCompat.getColor(context, R.color.status_closed));
                break;
        }

        holder.itemView.setOnClickListener(v -> {
            if (listener != null) {
                listener.onTicketClick(ticket);
            }
        });
    }

    @Override
    public int getItemCount() {
        return ticketList != null ? ticketList.size() : 0;
    }

    public static class TicketViewHolder extends RecyclerView.ViewHolder {
        TextView tvTicketCode, tvStatusBadge, tvTitle, tvCategoryAndLocation, tvPriority, tvDate;

        public TicketViewHolder(@NonNull View itemView) {
            super(itemView);
            tvTicketCode = itemView.findViewById(R.id.tvTicketCode);
            tvStatusBadge = itemView.findViewById(R.id.tvStatusBadge);
            tvTitle = itemView.findViewById(R.id.tvTitle);
            tvCategoryAndLocation = itemView.findViewById(R.id.tvCategoryAndLocation);
            tvPriority = itemView.findViewById(R.id.tvPriority);
            tvDate = itemView.findViewById(R.id.tvDate);
        }
    }
}
