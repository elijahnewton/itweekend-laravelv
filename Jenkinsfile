pipeline {
    agent any
    
    environment {
        // Define your Docker Registry info
        DOCKER_REGISTRY = "musiitwa"
        IMAGE_NAME = "it-weekend-lms"
        DOCKER_IMAGE = "${DOCKER_REGISTRY}/${IMAGE_NAME}"
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Build & Push Image') {
            steps {
                script {
                    // Build the image using the Jenkins Build Number as a tag
                    sh "docker build -t ${DOCKER_IMAGE}:${BUILD_NUMBER} ."
                    
                    // Push to registry (Assumes you ran 'docker login' on the Jenkins node)
                    sh "docker push ${DOCKER_IMAGE}:${BUILD_NUMBER}"
                }
            }
        }

        stage('Deploy') {
            steps {
                // Use 'withCredentials' to fetch secrets from Jenkins Vault
                withCredentials([
                    string(credentialsId: 'app-key', variable: 'SECRET_KEY'),
                    string(credentialsId: 'db-pass', variable: 'DB_PWD')
                ]) {
                    script {
                        // 1. Create the .env file dynamically on the target server
                        // This handles all 40+ variables without committing them to Git
                        sh """
                        cat <<EOF > .env
                        APP_NAME="IT Weekend LMS"
                        APP_ENV=production
                        APP_KEY=${SECRET_KEY}
                        APP_DEBUG=false
                        APP_URL=https://lms.file-share.page
                        
                        DB_CONNECTION=pgsql
                        DB_HOST=database-1-instance-1.cif4cooyawid.us-east-1.rds.amazonaws.com
                        DB_PORT=5432
                        DB_DATABASE=lms
                        DB_USERNAME=postgres
                        DB_PASSWORD=${DB_PWD}
                        
                        REDIS_HOST=database-1-instance-1.cif4cooyawid.us-east-1.rds.amazonaws.com
                        QUEUE_CONNECTION=redis
                        # ... Add other variables from your .env.example here
                        EOF
                        """

                        // 2. Run Docker Compose
                        // It will use the .env we just created and the image we just pushed
                        sh "BUILD_NUMBER=${BUILD_NUMBER} DOCKER_IMAGE=${DOCKER_IMAGE} docker compose up -d"
                    }
                }
            }
        }
    }
    
    post {
        always {
            // Clean up the .env file for security
            sh "rm -f .env"
        }
    }
}
