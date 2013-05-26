#!/bin/bash
SCRIPT_DIRECTORY=`( cd -P $(dirname $0); pwd)`

# For unit test of the Client
dd if=/dev/urandom of=${SCRIPT_DIRECTORY}/file-to-upload bs=1k count=1000

# For unit test of the server
mkdir ${SCRIPT_DIRECTORY}/server