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
                // We exclude composer.phar from the zip since production doesn't need it
                sh 'tar -czf it-weekend-lms.tar.gz --exclude=it-weekend-lms.tar.gz --exclude=./node_modules --exclude=./.git --exclude=composer.phar .'
            }
        }
        stage('Ship to Ansible') {
            steps {
                sshagent(['ec2-ssh-key']) {
                    sh "scp -o StrictHostKeyChecking=no ${APP_NAME}.tar.gz admin@${ANSIBLE_SERVER}:/tmp/"
                }
            }
        }
        stage('Deploy') {
            steps {
                sshagent(['ec2-ssh-key']) {
                    // This triggers the playbook on your Ansible server
                    sh "ssh admin@54.198.61.7 'ANSIBLE_HOST_KEY_CHECKING=False ansible-playbook -i laravel-lms/inventory.ini laravel-lms/deploy.yml'"
                }
            }
        }
    }
}
