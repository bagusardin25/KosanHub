// Jenkinsfile KosanHub
// Checkout -> Test -> Build Image -> Push Image -> Deploy -> Smoke Test (+ cek container worker)
//
// Credentials yang harus dibuat di Jenkins (Manage Jenkins > Credentials):
//   dockerhub-credentials : Username with password (username Docker Hub + Personal Access Token)
//   kosanhub-env          : Secret file (isi file .env production, lihat .env.example)
//   github-credentials    : Username with password (username GitHub + PAT) - hanya jika repo private,
//                           dipilih di konfigurasi job (Pipeline script from SCM), bukan di file ini.
//
// Bisa jalan di agent Linux (sh) maupun Windows (bat) tanpa diubah.

def run(String cmd) {
    if (isUnix()) {
        sh cmd
    } else {
        bat cmd
    }
}

def runOutput(String cmd) {
    if (isUnix()) {
        return sh(script: cmd, returnStdout: true).trim()
    }
    return bat(script: "@${cmd}", returnStdout: true).trim()
}

@NonCPS
def ambilId(String json) {
    def m = json =~ /"id"\s*:\s*(\d+)/
    return m.find() ? m.group(1) : null
}

pipeline {
    agent any

    triggers {
        // Dipicu GitHub webhook (payload URL: https://<domain-ngrok>/github-webhook/)
        githubPush()
    }

    options {
        // Checkout dilakukan di stage 'Checkout' sendiri supaya terlihat di Stage View.
        skipDefaultCheckout(true)
        disableConcurrentBuilds()
        buildDiscarder(logRotator(numToKeepStr: '20'))
    }

    environment {
        // GANTI dengan username Docker Hub kelompok (bukan rahasia, boleh ditulis di sini)
        DOCKERHUB_USER = 'ganti-username-dockerhub'
        IMAGE = "${DOCKERHUB_USER}/kosanhub"
        TAG = "${env.BUILD_NUMBER}"
        // Alamat aplikasi dari sisi agent Jenkins. Jika Jenkins berjalan di dalam container Docker,
        // ganti menjadi http://host.docker.internal:8000
        SMOKE_URL = 'http://localhost:8000'
    }

    stages {
        stage('Checkout') {
            steps {
                script {
                    def scmVars = checkout(scm)
                    env.GIT_SHORT = scmVars.GIT_COMMIT ? scmVars.GIT_COMMIT.substring(0, 7) : 'unknown'
                }
                echo "Commit: ${env.GIT_SHORT}"
            }
        }

        stage('Test') {
            steps {
                // Test dijalankan di dalam container (stage "test" pada Dockerfile).
                // Jika ada test gagal, docker build gagal dan pipeline berhenti di sini.
                script {
                    run("docker build --progress=plain --target test -t ${IMAGE}:test .")
                }
            }
        }

        stage('Build Image') {
            steps {
                script {
                    run("docker build --build-arg APP_VERSION=${TAG} -t ${IMAGE}:${TAG} -t ${IMAGE}:latest .")
                }
            }
        }

        stage('Push Image') {
            steps {
                withCredentials([usernamePassword(credentialsId: 'dockerhub-credentials',
                        usernameVariable: 'DOCKERHUB_LOGIN', passwordVariable: 'DOCKERHUB_TOKEN')]) {
                    script {
                        if (isUnix()) {
                            sh 'echo "$DOCKERHUB_TOKEN" | docker login -u "$DOCKERHUB_LOGIN" --password-stdin'
                        } else {
                            bat '@echo %DOCKERHUB_TOKEN%| docker login -u %DOCKERHUB_LOGIN% --password-stdin'
                        }
                    }
                }
                script {
                    run("docker push ${IMAGE}:${TAG}")
                    run("docker push ${IMAGE}:latest")
                }
            }
        }

        stage('Deploy') {
            steps {
                withCredentials([file(credentialsId: 'kosanhub-env', variable: 'ENV_FILE')]) {
                    script {
                        if (isUnix()) {
                            sh 'cp "$ENV_FILE" .env'
                        } else {
                            bat 'copy /Y "%ENV_FILE%" .env'
                        }
                    }
                }
                script {
                    // Image app & worker diambil dari Docker Hub (hasil stage Push), lalu container dibuat ulang.
                    run('docker compose pull app worker')
                    run('docker compose up -d --remove-orphans')
                    run('docker compose ps')
                }
            }
        }

        stage('Smoke Test') {
            steps {
                script {
                    // 1) GET /health harus 200. curl -f membuat perintah gagal jika status bukan 2xx.
                    def sehat = false
                    for (int i = 1; i <= 12 && !sehat; i++) {
                        sleep(time: 5, unit: 'SECONDS')
                        try {
                            echo runOutput("curl -fsS ${SMOKE_URL}/health")
                            sehat = true
                        } catch (err) {
                            echo "Percobaan ${i}/12: /health belum 200"
                        }
                    }
                    if (!sehat) {
                        error('Smoke Test gagal: /health tidak mengembalikan 200')
                    }

                    // 2) Tantangan KosanHub: picu pembuatan tagihan, pastikan container worker memprosesnya.
                    def respons = runOutput("curl -fsS -X POST -H \"Accept: application/json\" ${SMOKE_URL}/api/invoice-runs")
                    echo "Job tagihan dibuat: ${respons}"
                    def runId = ambilId(respons)
                    if (!runId) {
                        error('Smoke Test gagal: tidak mendapat id invoice-run')
                    }

                    def selesai = false
                    for (int i = 1; i <= 12 && !selesai; i++) {
                        sleep(time: 5, unit: 'SECONDS')
                        def status = runOutput("curl -fsS -H \"Accept: application/json\" ${SMOKE_URL}/api/invoice-runs/${runId}")
                        echo "Status job #${runId}: ${status}"
                        if (status.contains('"status":"FAILED"')) {
                            error("Smoke Test gagal: worker gagal memproses job #${runId}")
                        }
                        selesai = status.contains('"status":"DONE"')
                    }
                    if (!selesai) {
                        error("Smoke Test gagal: worker tidak memproses job #${runId} dalam 60 detik")
                    }

                    run('docker compose logs --tail 20 worker')
                }
            }
        }
    }

    post {
        success {
            echo "==================== RINGKASAN ====================\n" +
                 "STATUS : SUKSES\n" +
                 "Build  : #${env.BUILD_NUMBER} (commit ${env.GIT_SHORT})\n" +
                 "Image  : ${IMAGE}:${TAG} dan ${IMAGE}:latest\n" +
                 "Deploy : ${SMOKE_URL} (health OK, worker tagihan OK)\n" +
                 "==================================================="
        }
        failure {
            echo "==================== RINGKASAN ====================\n" +
                 "STATUS : GAGAL\n" +
                 "Build  : #${env.BUILD_NUMBER} (commit ${env.GIT_SHORT})\n" +
                 "Cek stage yang berwarna merah di Stage View.\n" +
                 "Jika gagal di Test/Build, aplikasi versi lama tetap berjalan.\n" +
                 "==================================================="
        }
        always {
            script {
                // File .env berisi rahasia, jangan dibiarkan tertinggal di workspace.
                if (isUnix()) {
                    sh 'rm -f .env; docker logout || true'
                } else {
                    bat '''
                        if exist .env del /F /Q .env
                        docker logout
                        exit /b 0
                    '''
                }
            }
        }
    }
}
