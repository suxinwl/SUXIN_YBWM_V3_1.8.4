FROM ccr.ccs.tencentyun.com/w7team/swoole:fpm-php8.0
WORKDIR /data
COPY ./ /data/
RUN  apk add  supervisor
COPY ./supervisor.d /etc/supervisord.d
RUN supervisord -c /etc/supervisord.conf
#ADD yqwmNg.conf /usr/local/openresty/nginx/conf/vhost/
ADD php-cgi-80.sock /tmp/

EXPOSE 80
RUN /usr/local/openresty/nginx/sbin/nginx -c /data/nginx.conf
RUN chown -R root:root /data \
    && chmod -R 755 /data
CMD ["sh", "/data/start.sh"]
