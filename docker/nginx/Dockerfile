FROM nginx:1.27-alpine

# Remove configuração default
RUN rm /etc/nginx/conf.d/default.conf

# Copia a config customizada
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Copia apenas a pasta public do Laravel
WORKDIR /var/www/html
COPY public ./public

# Ajusta permissões para leitura pelo Nginx
RUN chown -R nginx:nginx /var/www/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
