#!/bin/bash

PHP_ONLINE=7.4
PHP_CURRENT=$(php -v | grep -oP '(?<=PHP )\d\.\d')

if [ "$PHP_ONLINE" != "$PHP_CURRENT" ]; then
    echo "###############################################"
    echo "  Online usiamo PHP $PHP_ONLINE, mentre in locale $PHP_CURRENT"
    echo "           vuoi sminchiare tutto?? :)          "
    echo "            interrompo l'esecuzione          "
    echo "###############################################"
    exit 1
else
    echo "########################################"
    echo "  Online usiamo PHP $PHP_ONLINE, in locale $PHP_CURRENT"
    echo "          tutto ok, procediamo! :)                  "
    echo "########################################"
fi

composer update

echo ""
echo "Su quale ambiente vuoi effettuare il deploy?"
echo "1) beta"
echo "emmebi) emmebi-manager.gktgroup.it"
echo
read -p "Scelta: " choice

case $choice in
    1)
        echo "Deploy in corso su beta..."
        echo "Ricorda: y07RX%CgY3M*f9DAc2ca9GwqsJ"
        rsync -rz --info=progress2 --delete --exclude-from deploy-excludes.txt ./ gktma9066@168.119.44.48:/home/gktmanager.test-demo.it/public_html/

        echo
        echo "Deploy su beta completato."
        ;;

    emmebi)
        echo "Deploy in corso su emmebi-manager.gktgroup.it..."
        echo "Ricorda: K46sfGiYebGR9R7NHosCfvX"
        rsync -rz --info=progress2 --delete --exclude-from deploy-excludes.txt ./ emmeb7580@168.119.44.48:/home/emmebi-manager.gktgroup.it/public_html/

        echo
        echo "Deploy su emmebi-manager.gktgroup.it completato."
        ;;

    *)
        echo "Scelta non valida."
        ;;
esac
