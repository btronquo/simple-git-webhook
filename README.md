# SIMPLE GIT WEBHOOK

## Description
The main goal is to have only one entry point to syncronise many differents repositories.
I mixed some code from internet to provide a simple and reusable git webhook script


## Prerequisites:
- Git command line available
- Key ssh set on the repository settings
- The repository is cloned and the branch name is set correctly
- have a folder production/ with projects names like `production.myrepository`
- have a folder preproduction/ with projects names like `preproduction.myrepository`

## How to ?
- copy `.htaccess.example`, fill with correct env token and rename it to `.htaccess`
- copy `deployer.example.php`, fill with correct informations and rename it to `deployer.php`
- be sure to have the same token set on your github repository settings > webhook
- example with github: you can set a url like that (in settings > webhooks): `https://githook.yourdomain.com/deployer.php?name=myrepository`


## Licence
- MIT
