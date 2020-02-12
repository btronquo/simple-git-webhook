# SIMPLE GIT WEBHOOK

## Description
The main goal is to have only one entry point to syncronise many differents repositories.
I mixed some code from internet to provide a simple and reusable git webhook script


## Prerequisites:
- Git command line available
- Key ssh set on the repository settings
- The repository is cloned and the branch name is set correctly


## How to ?
- rename and modify WEBHOOK_KEY in the file .htaccess.example
- add the same on the github repository settings > webhook
- upload the content
- example with github: you can set a url like that (in settings > webhooks): `https://githook.yourdomain.com/deployer.php?name=myrepository`


## Licence
- MIT
