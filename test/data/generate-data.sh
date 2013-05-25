#!/bin/bash
SCRIPT_DIRECTORY=`( cd -P $(dirname $0); pwd)`
dd if=/dev/urandom of=${SCRIPT_DIRECTORY}/file-to-upload bs=1k count=1000