FROM nginx:alpine

# Copy static single-page app
COPY index.html /usr/share/nginx/html/index.html

# Expose standard web port for Render
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
