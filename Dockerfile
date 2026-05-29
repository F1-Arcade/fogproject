FROM debian:bookworm-slim AS base

ARG S6_OVERLAY_VERSION=3.2.0.2
ARG TARGETARCH

# ─── s6-overlay (combined into a single RUN) ─────────────────────────────────
RUN apt-get update && \
    apt-get install -y --no-install-recommends wget xz-utils ca-certificates && \
    if [ "$TARGETARCH" = "arm64" ]; then S6_ARCH=aarch64; \
    elif [ "$TARGETARCH" = "amd64" ]; then S6_ARCH=x86_64; \
    else S6_ARCH="$TARGETARCH"; fi && \
    wget -qO- "https://github.com/just-containers/s6-overlay/releases/download/v${S6_OVERLAY_VERSION}/s6-overlay-noarch.tar.xz" | tar -C / -Jxpf - && \
    wget -qO- "https://github.com/just-containers/s6-overlay/releases/download/v${S6_OVERLAY_VERSION}/s6-overlay-${S6_ARCH}.tar.xz" | tar -C / -Jxpf - && \
    apt-get purge -y --auto-remove wget xz-utils && \
    rm -rf /var/lib/apt/lists/*

# ─── FOG runtime dependencies (trimmed: dropped gcc/g++/cpp/m4/openssh/etc.) ──
RUN apt-get update && apt-get install -y --no-install-recommends \
    apache2 \
    curl \
    genisoimage \
    iproute2 \
    lftp \
    libapache2-mod-fcgid \
    mariadb-client \
    nfs-kernel-server \
    rpcbind \
    php-cli \
    php-curl \
    php-fpm \
    php-gd \
    php-ldap \
    php-mbstring \
    php-mysql \
    tftpd-hpa \
    tftp-hpa \
    vsftpd \
    && rm -rf /var/lib/apt/lists/*

# ─── Detect PHP version (single source of truth) ─────────────────────────────
RUN PHP_VER=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;') && \
    echo "$PHP_VER" > /etc/.php_ver

# ─── User, directories, FOG app, configuration (one layer) ───────────────────
RUN useradd -r -d /opt/fog -s /bin/bash fogproject && \
    mkdir -p /opt/fog/log /opt/fog/service/etc /opt/fog/snapins \
             /images /images/dev /tftpboot /var/www/html/fog && \
    touch /images/.mntcheck /images/dev/.mntcheck

# ─── FOG application (separate COPYs so changing one doesn't invalidate all) ─
COPY packages/web/ /var/www/html/fog/
COPY packages/service/ /opt/fog/service/
COPY packages/tftp/ /tftpboot/

# ─── Permissions, symlinks, PHP tuning, Apache modules (one layer) ───────────
RUN chown -R www-data:www-data /var/www/html/fog && \
    chown -R fogproject:fogproject /opt/fog /images /tftpboot && \
    find /opt/fog/service -name "FOG*" -not -name "*.php" -exec chmod +x {} \; && \
    ln -sf /opt/fog/log /var/log/fog && \
    ln -sf /opt/fog/service/etc /etc/fog && \
    PHP_VER=$(cat /etc/.php_ver) && \
    for INI in "/etc/php/${PHP_VER}/fpm/php.ini" "/etc/php/${PHP_VER}/cli/php.ini"; do \
        sed -i \
            -e 's/post_max_size = 8M/post_max_size = 3000M/' \
            -e 's/upload_max_filesize = 2M/upload_max_filesize = 3000M/' \
            -e 's/.*max_input_vars.*/max_input_vars = 250000/' \
            "$INI"; \
    done && \
    a2enmod rewrite proxy proxy_fcgi setenvif ssl && \
    a2dissite 000-default

# ─── Container overlay (init script + service definitions) ───────────────────
COPY docker/rootfs/ /

# ─── Normalise line endings and set executables ──────────────────────────────
RUN find /etc/cont-init.d /etc/services.d -type f -exec sed -i 's/\r$//' {} \; && \
    chmod +x /etc/cont-init.d/* /etc/services.d/.fog-wait && \
    find /etc/services.d -name "run" -exec chmod +x {} \;

# ─── Healthcheck (HTTP 200/302 on FOG management page) ───────────────────────
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl -fsS -o /dev/null -w '%{http_code}' http://localhost/fog/management/ | grep -qE '^(200|302)$' || exit 1

EXPOSE 80 443 69/udp 21 2049 20048

# Keep container env visible to s6 init/services
ENV S6_KEEP_ENV=1

ENTRYPOINT ["/init"]
