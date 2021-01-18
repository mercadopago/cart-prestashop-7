#!/bin/bash

if [ -f "./vendor/bin/php-cs-fixer" ]
then
    echo "Composer OK"
else
    composer update
fi


for var in "$@"
do
    ./vendor/bin/php-cs-fixer fix $var --rules=@PSR2
    ./vendor/bin/phpcbf -d $var --standard=PSR2
    echo "CodeSniffer OK"
done
