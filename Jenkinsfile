pipeline {
    agent any

    stages {
        stage('Checkout') {
            steps {
                git branch: 'main',
                    url: 'https://github.com/Mn2268/Projet-DevOps.git'
            }
        }

        stage('Build Docker Image') {
            steps {
                sh 'docker build -t projet-devops-app .'
            }
        }

        stage('Run Containers') {
            steps {
                sh 'docker-compose up -d'
            }
        }
    }

    post {
        always {
            echo 'Pipeline finished'
        }
        success {
            echo 'Build and deployment successful'
        }
        failure {
            echo 'Pipeline failed'
        }
    }
}
