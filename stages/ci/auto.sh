#!/bin/bash

project=esmeralda-api
repo=ec2-user@ec2-23-23-245-121.compute-1.amazonaws.com:38022

SSH_AUTH_SOCK=/tmp/ssh-1V66bPzMYoeP/agent.5506; 
export SSH_AUTH_SOCK;
SSH_AGENT_PID=5507; 
export SSH_AGENT_PID;

if [ -z "$(git remote | grep deployer)" ]; then
    git remote add deployer ssh://$repo/~/git_repo/$project.git
fi

git push deployer
