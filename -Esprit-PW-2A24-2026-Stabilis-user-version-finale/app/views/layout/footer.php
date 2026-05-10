    </main>
    
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h6>© 2024 Back-Office - Gestion des Défis Écologiques</h6>
                    <p class="mb-0">Développé avec PHP & MySQL</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="text-light-50">Démo Stabilis</span>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>
    <script src="assets/js/back-script.js"></script>
    
    <script>
        // Initialize stats modal functionality
        const statsBtn = document.getElementById('statsBtn');
        const statsModal = new bootstrap.Modal(document.getElementById('statsModal'));
        const refreshStatsBtn = document.getElementById('refreshStatsBtn');
        let defisChart = null;
        let participationsChart = null;

        if (statsBtn) {
            statsBtn.addEventListener('click', async function(e) {
                e.preventDefault();
                await loadAndDisplayStats();
            });
        }

        if (refreshStatsBtn) {
            refreshStatsBtn.addEventListener('click', async function(e) {
                e.preventDefault();
                await loadAndDisplayStats();
            });
        }

        async function loadAndDisplayStats() {
            try {
                const response = await fetch('../app/api/stats.php');
                const data = await response.json();

                if (data.success) {
                    updateStatsDisplay(data);
                    statsModal.show();
                } else {
                    alert('Erreur: ' + (data.error || 'Impossible de charger les statistiques'));
                }
            } catch (error) {
                console.error('Error loading stats:', error);
                alert('Erreur lors du chargement des statistiques');
            }
        }

        function updateStatsDisplay(data) {
            // Update Défis stats with percentages
            document.getElementById('defisTotal').textContent = data.defis.total || 0;
            document.getElementById('defisAliment').textContent = data.defis.aliment || 0;
            document.getElementById('defisEntrainement').textContent = data.defis.entrainement || 0;
            document.getElementById('defisCompensation').textContent = data.defis.compensation || 0;

            // Update percentages and progress bars
            document.getElementById('defisAlimentPercent').textContent = (data.defis.percentages?.aliment || 0) + '%';
            document.getElementById('defisEntrainementPercent').textContent = (data.defis.percentages?.entrainement || 0) + '%';
            document.getElementById('defisCompensationPercent').textContent = (data.defis.percentages?.compensation || 0) + '%';

            document.getElementById('defisAlimentBar').style.width = (data.defis.percentages?.aliment || 0) + '%';
            document.getElementById('defisEntrainementBar').style.width = (data.defis.percentages?.entrainement || 0) + '%';
            document.getElementById('defisCompensationBar').style.width = (data.defis.percentages?.compensation || 0) + '%';

            // Update Participations stats with percentages
            document.getElementById('participationsTotal').textContent = data.participations.total || 0;
            document.getElementById('participationsEnCoursCount').textContent = data.participations.en_cours || 0;
            document.getElementById('participationsReussiCount').textContent = data.participations.reussi || 0;
            document.getElementById('participationsEchoueCount').textContent = data.participations.echoue || 0;

            document.getElementById('participationsEnCours').textContent = data.participations.en_cours || 0;
            document.getElementById('participationsReussi').textContent = data.participations.reussi || 0;

            // Update percentages and progress bars for participations
            document.getElementById('participationsEnCoursPercent').textContent = (data.participations.percentages?.en_cours || 0) + '%';
            document.getElementById('participationsReussiPercent').textContent = (data.participations.percentages?.reussi || 0) + '%';
            document.getElementById('participationsEchouePercent').textContent = (data.participations.percentages?.echoue || 0) + '%';

            document.getElementById('participationsEnCoursBar').style.width = (data.participations.percentages?.en_cours || 0) + '%';
            document.getElementById('participationsReussiBar').style.width = (data.participations.percentages?.reussi || 0) + '%';
            document.getElementById('participationsEchoueBar').style.width = (data.participations.percentages?.echoue || 0) + '%';

            // Update timestamp
            document.getElementById('statsTimestamp').textContent = new Date(data.timestamp).toLocaleTimeString('fr-FR');

            // Create charts
            createDefisChart(data.defis);
            createParticipationsChart(data.participations);
        }

        function createDefisChart(defisData) {
            const ctx = document.getElementById('defisChart').getContext('2d');
            
            if (defisChart) defisChart.destroy();
            
            defisChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Alimentaire', 'Entraînement', 'Compensation'],
                    datasets: [{
                        data: [defisData.aliment || 0, defisData.entrainement || 0, defisData.compensation || 0],
                        backgroundColor: ['#22c55e', '#f59e0b', '#06b6d4'],
                        borderColor: ['#fff', '#fff', '#fff'],
                        borderWidth: 3,
                        borderRadius: 8,
                        spacing: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 20,
                                font: {
                                    size: 13,
                                    weight: 600,
                                    family: "'Inter', sans-serif"
                                },
                                color: '#374151',
                                generateLabels: function(chart) {
                                    const data = chart.data;
                                    if (data.labels.length && data.datasets.length) {
                                        return data.labels.map((label, i) => ({
                                            text: `${label}: ${data.datasets[0].data[i]}`,
                                            fillStyle: data.datasets[0].backgroundColor[i],
                                            hidden: false,
                                            index: i,
                                            pointStyle: 'circle'
                                        }));
                                    }
                                    return [];
                                }
                            }
                        }
                    },
                    cutout: '65%'
                }
            });
        }

        function createParticipationsChart(participationsData) {
            const ctx = document.getElementById('participationsChart').getContext('2d');
            
            if (participationsChart) participationsChart.destroy();
            
            participationsChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['En cours', 'Terminée', 'Échouée'],
                    datasets: [{
                        data: [participationsData.en_cours || 0, participationsData.reussi || 0, participationsData.echoue || 0],
                        backgroundColor: ['#0d6efd', '#198754', '#dc3545'],
                        borderColor: ['#fff', '#fff', '#fff'],
                        borderWidth: 3,
                        borderRadius: 8,
                        spacing: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 20,
                                font: {
                                    size: 13,
                                    weight: 600,
                                    family: "'Inter', sans-serif"
                                },
                                color: '#374151',
                                generateLabels: function(chart) {
                                    const data = chart.data;
                                    if (data.labels.length && data.datasets.length) {
                                        return data.labels.map((label, i) => ({
                                            text: `${label}: ${data.datasets[0].data[i]}`,
                                            fillStyle: data.datasets[0].backgroundColor[i],
                                            hidden: false,
                                            index: i,
                                            pointStyle: 'circle'
                                        }));
                                    }
                                    return [];
                                }
                            }
                        }
                    },
                    cutout: '65%'
                }
            });
        }
    </script>
</body>
</html>
