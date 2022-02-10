#!/usr/bin/env bash

BASE_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )/..
TMP_DIR="/tmp/mercadopago"

shopt -s extglob

if [ -d "$TMP_DIR" ]; then
	rm -rf $TMP_DIR/*
fi

if [ ! -d "$TMP_DIR" ]; then
	mkdir $TMP_DIR
fi

cd $BASE_DIR
cp -r !(*.md|composer.*|package*.json|vendor|node_modules|phpcs.xml|scripts.js|bin) $TMP_DIR

if [ $? -ne 0 ]; then
	echo "Error copying files"
	exit 1
fi

cd $TMP_DIR/.. && zip -rX mercadopago.zip mercadopago -x "**/.DS_Store" -x "*/.git/*"

mv $TMP_DIR/../mercadopago.zip $BASE_DIR && rm -rf $TMP_DIR

echo "Package created successfully"
