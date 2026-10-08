# Cursor Cloud Agent base image for kibbles.
# Do not COPY the project; Cursor checks out the repo onto this image.
FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive \
    LANG=C.UTF-8 \
    LC_ALL=C.UTF-8

# git + sudo are required for Cloud Agent checkout. The remaining libraries
# are Playwright Chromium deps from playwright-core 1.63 (Ubuntu 24.04).
RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        fonts-freefont-ttf \
        fonts-ipafont-gothic \
        fonts-liberation \
        fonts-noto-color-emoji \
        fonts-tlwg-loma-otf \
        fonts-unifont \
        fonts-wqy-zenhei \
        git \
        gnupg \
        libasound2t64 \
        libatk-bridge2.0-0t64 \
        libatk1.0-0t64 \
        libatspi2.0-0t64 \
        libcairo2 \
        libcups2t64 \
        libdbus-1-3 \
        libdrm2 \
        libfontconfig1 \
        libfreetype6 \
        libgbm1 \
        libglib2.0-0t64 \
        libnspr4 \
        libnss3 \
        libpango-1.0-0 \
        libx11-6 \
        libxcb1 \
        libxcomposite1 \
        libxdamage1 \
        libxext6 \
        libxfixes3 \
        libxkbcommon0 \
        libxrandr2 \
        openssh-client \
        sqlite3 \
        sudo \
        unzip \
        xfonts-cyrillic \
        xfonts-scalable \
        xvfb \
        zip \
    && rm -rf /var/lib/apt/lists/*

# PHP 8.5 matches composer.json (^8.5) and .github/workflows/tests.yml.
# Snapshot Composer is required: stable 2.10.3 lacks config.policy.cooldown (2.11+).
RUN install -d -m 0755 /etc/apt/keyrings \
    && curl -fsSL 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x4f4ea0aae5267a6c' \
        | gpg --dearmor -o /etc/apt/keyrings/ondrej-php.gpg \
    && echo 'deb [signed-by=/etc/apt/keyrings/ondrej-php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble main' \
        > /etc/apt/sources.list.d/ondrej-php.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        php8.5-cli \
        php8.5-bcmath \
        php8.5-curl \
        php8.5-intl \
        php8.5-mbstring \
        php8.5-pcov \
        php8.5-sqlite3 \
        php8.5-xml \
        php8.5-zip \
    && printf '%s\n' 'zend.assertions=1' > /etc/php/8.5/cli/conf.d/99-assertions.ini \
    && curl -fsSL https://getcomposer.org/composer.phar -o /usr/local/bin/composer \
    && chmod +x /usr/local/bin/composer \
    && rm -rf /var/lib/apt/lists/*

# The repo's package manager is bun (see package.json devEngines), not npm/pnpm.
RUN curl -fsSL https://bun.sh/install | BUN_INSTALL=/usr/local bash

RUN printf '%s\n' 'ubuntu ALL=(ALL) NOPASSWD:ALL' > /etc/sudoers.d/ubuntu \
    && chmod 0440 /etc/sudoers.d/ubuntu
