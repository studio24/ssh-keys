## Studio 24 access to client servers

We lock down remote server access to our office IP address (supplied on request) and to use SSH keys to login. 
This repo contains instructions on how to add your SSH key and how to distribute staff public SSH keys to remote servers via Ansible.  

## Adding your public key

Ensure your current public key is stored in the `staff/` folder, please note this should only contain current Studio 24 staff.

Clone the project:

```
mkdir -p ~/Sites/studio24
git clone git@github.com:studio24/ssh-keys.git ~/Sites/studio24/ssh-keys
cd /Sites/studio24/ssh-keys
```

Update your key (replace `simon_jones.pub` with your name):

```
cp ~/.ssh/id_ed25519.pub staff/simon_jones.pub
```

Please add your public key changes to a branch and create a Pull Request to merge into main. Please note only [codeowners](.github/CODEOWNERS) have permissions to approve Pull Requests.

## Removing public keys

When staff leave Studio 24 ensure their public keys are removed from this repo.

## Server setup

The following instructions automate distributing staff public SSH keys to client servers every half hour.

### 1. Create user

The SSH user you wish to connect to via passwordless logins must already exist. This is normally the `studio24` or `deploy` user.

You can change the SSH user in the `config.yml` file, this defaults to `studio24`. 

Please note the SSH key update process does not require any sudo rights for the user, it is designed to be used with normal, restricted user permissions.

### 2. Setup

Install Ansible (v 2.4+), if it is not already available. The following instructions are for Centos.

```
sudo yum install epel-release
sudo yum install ansible

# Test Ansible is installed
ansible --version
```

You can run the following setup Ansible playbook to automate the next steps.

```
export TEMP=/root/.ansible/tmp
ansible-pull -U https://github.com/studio24/ssh-keys.git setup.yml
```

#### Manual instructions

Manual instructions appear below. 

```
touch /var/log/ansible-pull.log
chown studio24 /var/log/ansible-pull.log
chmod 644 /var/log/ansible-pull.log

echo "/var/log/ansible-pull.log {
    rotate 7
    daily
    compress
    missingok
    notifempty
}
      
" >> /etc/logrotate.d/ansible-pull

echo "# Cron job to run SSH Keys import script every half hour
TEMP=/home/studio24/.ansible/tmp
*/30 * * * * studio24 ansible-pull -d /home/studio24/repo --only-if-changed -U https://github.com/studio24/ssh-keys.git >> /var/log/ansible-pull.log 2>&1

" >> /etc/cron.d/ansible-pull
```

### 3. Test run 

You can check this works by running the following command as the SSH user (not root!). This should copy the public keys to `/home/studio24/.ssh/authorized_keys` and set the correct permissions. 

```
su studio24
export TEMP=/home/studio24/.ansible/tmp

ansible-pull -U https://github.com/studio24/ssh-keys.git
``` 
