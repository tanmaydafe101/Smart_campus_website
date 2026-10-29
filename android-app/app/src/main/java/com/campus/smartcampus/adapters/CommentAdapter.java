package com.campus.smartcampus.adapters;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.campus.smartcampus.R;
import com.campus.smartcampus.models.Comment;

import java.util.List;

public class CommentAdapter extends RecyclerView.Adapter<CommentAdapter.CommentViewHolder> {
    private Context context;
    private List<Comment> commentList;

    public CommentAdapter(Context context, List<Comment> commentList) {
        this.context = context;
        this.commentList = commentList;
    }

    @NonNull
    @Override
    public CommentViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.item_comment, parent, false);
        return new CommentViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull CommentViewHolder holder, int position) {
        Comment comment = commentList.get(position);
        holder.tvAuthorName.setText(comment.getAuthorName());
        holder.tvAuthorRole.setText(comment.getAuthorRole().toUpperCase());
        holder.tvCommentMessage.setText(comment.getMessage());
        holder.tvCommentDate.setText(comment.getCreatedAt());
    }

    @Override
    public int getItemCount() {
        return commentList != null ? commentList.size() : 0;
    }

    public static class CommentViewHolder extends RecyclerView.ViewHolder {
        TextView tvAuthorName, tvAuthorRole, tvCommentMessage, tvCommentDate;

        public CommentViewHolder(@NonNull View itemView) {
            super(itemView);
            tvAuthorName = itemView.findViewById(R.id.tvAuthorName);
            tvAuthorRole = itemView.findViewById(R.id.tvAuthorRole);
            tvCommentMessage = itemView.findViewById(R.id.tvCommentMessage);
            tvCommentDate = itemView.findViewById(R.id.tvCommentDate);
        }
    }
}
