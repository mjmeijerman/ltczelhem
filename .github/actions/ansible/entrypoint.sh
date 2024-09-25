#!/bin/sh

set -e

# Write the password file
echo "$VAULT_PASS" > ~/.vault_pass.txt
mkdir -p ~/.ssh
ansible-vault view --vault-password-file ~/.vault_pass.txt ansible/deploy/deploy.secret > ~/.ssh/id_rsa
chmod 0600 ~/.ssh/id_rsa

set -x
ansible-playbook ansible/deploy/deploy.yml \
    --inventory-file=ansible/hosts \
    --vault-password-file ~/.vault_pass.txt \
    --key-file ~/.ssh/id_rsa \
    --limit=${INPUT_LIMIT} \
    --extra-vars="project_version=${INPUT_VERSION}" \
    ${INPUT_VERBOSITY}
