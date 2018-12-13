FROM php:7.2

ADD https://github.com/louislivi/smproxy/releases/download/v1.2.5/smproxy.tar.gz /usr/local
RUN printf "\n" | pecl install -f swoole && \
    docker-php-ext-enable swoole && \
    cd /usr/local && \
    tar -zxvf smproxy.tar.gz && \
    ls -lna
VOLUME /usr/local/smproxy/conf
VOLUME /usr/local/smproxy/logs

EXPOSE 3366

CMD ["/usr/local/smproxy/SMProxy", "start --console"]
