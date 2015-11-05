#!/bin/bash
sudo apt-get update && sudo apt-get upgrade && sudo apt-get dist-upgrade
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )";
cd "$DIR/../.." && hg pull -u
