pipeline {
    agent any
    environment {
        ANSIBLE_SERVER = "54.198.61.7" // Your Ansible Control Node Private IP
        APP_NAME = "it-weekend-lms"
    }
    stages {
        stage('Checkout') {
            steps {
                git branch: 'main', url: 'https://github.com/elijahnewton/itweekend-laravelv.git'
            }
        }
        stage('Build Backend') {
            steps {
                // Using your local composer.phar
                sh 'php composer.phar install --no-dev --optimize-autoloader'
            }
        }
        stage('Build Frontend') {
            steps {
                sh 'npm install && npm run build'
            }
        }
        stage('Package Artifact') {
            steps {
                // We create the zip in /tmp/ so tar doesn't try to compress itself
                sh 'tar -czf /tmp/${APP_NAME}.tar.gz --exclude=node_modules --exclude=.git --exclude=composer.phar .'
            }
        }
        stage('Ship to Ansible') {
            steps {
                sshagent(['ec2-ssh-key']) {
                    // Reference the file from /tmp/
                    sh "scp -o StrictHostKeyChecking=no /tmp/${APP_NAME}.tar.gz admin@${ANSIBLE_SERVER}:/tmp/"
                    
                    // Cleanup the local /tmp/ file immediately after shipping to keep your 1.55GB safe
                    sh "rm /tmp/${APP_NAME}.tar.gz"
                }
            }
        }
        stage('Deploy') {
            steps {
                sshagent(['ec2-ssh-key']) {
                    // This triggers the playbook on your Ansible server
                    sh "ssh admin@${ANSIBLE_SERVER} 'ansible-playbook -i laravel-lms/inventory.ini laravel-lms/deploy.yml'"
                }
            }
        }
    }
}
