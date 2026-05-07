pipeline {
    agent any
    
    environment {
        DOCKER_REGISTRY = "musiitwa"
        IMAGE_NAME = "it-weekend-lms"
        DOCKER_IMAGE = "${DOCKER_REGISTRY}/${IMAGE_NAME}"
    }

    stages {
        stage('Build & Push') {
            steps {
                script {
                    sh "docker build -t ${DOCKER_IMAGE}:${BUILD_NUMBER} ."
                    sh "docker push ${DOCKER_IMAGE}:${BUILD_NUMBER}"
                }
            }
        }

        stage('Provision Database') {
            steps {
                withCredentials([string(credentialsId: 'db-pass', variable: 'DB_PWD')]) {
                    script {
                        // Connect to the 'postgres' (default) database to check/create 'lms'
                        sh """
                        export PGPASSWORD=${DB_PWD}
                        psql -h database-1-instance-1.cif4cooyawid.us-east-1.rds.amazonaws.com \
                             -U postgres -d postgres -tc "SELECT 1 FROM pg_database WHERE datname = 'lms'" | grep -q 1 || \
                        psql -h database-1-instance-1.cif4cooyawid.us-east-1.rds.amazonaws.com \
                             -U postgres -d postgres -c "CREATE DATABASE lms;"
                        """
                    }
                }
            }
        }

        stage('Deploy') {
            steps {
                // Pulling secret RDS credentials from Jenkins Credential Store
                withCredentials([
                    string(credentialsId: 'app-key', variable: 'SECRET_KEY'),
                    string(credentialsId: 'db-pass', variable: 'DB_PWD')
                ]) {
                    script {
                        // Generate .env file dynamically
                        // We set CACHE, SESSION, and QUEUE to 'database'
                        sh """
                        cat <<EOF > .env
                        APP_NAME="IT Weekend LMS"
                        APP_ENV=production
                        APP_KEY=${SECRET_KEY}
                        APP_URL=https://your-lms-site.com
                        
                        DB_CONNECTION=pgsql
                        DB_HOST=database-1-instance-1.cif4cooyawid.us-east-1.rds.amazonaws.com
                        DB_PORT=5432
                        DB_DATABASE=lms
                        DB_USERNAME=postgres
                        DB_PASSWORD=${DB_PWD}
                        
                        # REDIS REPLACEMENT CONFIG
                        CACHE_STORE=database
                        SESSION_DRIVER=database
                        QUEUE_CONNECTION=database
                        
                        # LOGGING
                        LOG_CHANNEL=stack
                        LOG_LEVEL=error
                        EOF
                        """

                        // Deploying the new version
                        sh "BUILD_NUMBER=${BUILD_NUMBER} DOCKER_IMAGE=${DOCKER_IMAGE} docker compose up -d"
                    }
                }
            }
        }
    }
    
    post {
        always {
            // Clean up secrets from the Jenkins workspace
            sh "rm -f .env"
        }
    }
}
