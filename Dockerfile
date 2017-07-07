FROM php:7.1-cli

RUN apt-get update && apt-get install -y \
    git \
    openssl \
    zip \
    unzip \
    zlib1g-dev \
    && apt-get clean

RUN docker-php-ext-install pdo mbstring zip

# Install ngrok (latest official stable from https://ngrok.com/download).
RUN curl -o ./ngrok.zip https://bin.equinox.io/c/4VmDzA7iaHb/ngrok-stable-linux-amd64.zip && \
    set -x \
    && unzip -o ./ngrok.zip -d /bin \
    && rm -f ./ngrok.zip

RUN curl -sS https://getcomposer.org/installer | \
    php -- --install-dir=/usr/local/bin --filename=composer

RUN curl -o /usr/bin/phpunit-watch \
    https://gist.githubusercontent.com/ngyuki/7786721/raw/1ca6a93a95d868218bac1fc92321b2911fd13e44/phpunit-watch && \
    chmod +x /usr/bin/phpunit-watch

ENV USERNAME dev
ENV HOME /home/$USERNAME

RUN useradd -ms /bin/bash $USERNAME

USER $USERNAME

WORKDIR $HOME

ENV PATH="$HOME/.composer/vendor/bin:$HOME/src/vendor/bin:${PATH}"

COPY . ./src

WORKDIR $HOME/src

# Change permissions in order to create vendor directory
USER root

RUN chown -R $USERNAME:$USERNAME $HOME
RUN chmod -R 700 $HOME

USER $USERNAME

RUN /bin/bash -l -c "mkdir -p $HOME/src/vendor"

RUN composer install

RUN cp .env.example .env

RUN phpunit

ENV PORT 8000

EXPOSE $PORT

CMD php -S 0.0.0.0:$PORT
