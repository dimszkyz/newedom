<footer class="footer">
    <div class="container-footer">
        <div class="footer-content">
            <div class="footer-column">
                <h3>Tentang Universitas</h3>

                <div class="about-wrapper">
                    <img src="{{ asset('assets/images/logo_unwnobg.png') }}"
                        alt="Logo Universitas Ngudi Waluyo"
                        class="about-logo">

                    <p class="about-text">
                        Sistem Evaluasi Dosen Oleh Mahasiswa (EDOM) Universitas
                        Ngudi Waluyo merupakan sarana evaluasi pembelajaran yang
                        digunakan untuk meningkatkan mutu akademik, kualitas
                        pengajaran dosen, serta pelayanan pendidikan secara
                        berkelanjutan.
                    </p>
                </div>
            </div>

            <div class="footer-column">
                <h3>Tautan Cepat</h3>

                <ul class="footer-links">
                    <li>
                        <a href="{{ url('/') }}">
                            Beranda Utama
                        </a>
                    </li>

                    <li>
                        <a href="#">
                            Panduan EDOM
                        </a>
                    </li>
                </ul>
            </div>

            <div class="footer-column">
                <h3>Hubungi Kami</h3>

                <div class="footer-item">
                    <i class="fas fa-location-dot"></i>
                    <a href="https://maps.google.com/?q=Universitas+Ngudi+Waluyo" target="_blank">
                        Jl. Diponegoro No.186, Gedanganak,
                        Kec. Ungaran Timur, Kab. Semarang
                    </a>
                </div>

                <div class="footer-item">
                    <i class="fas fa-phone"></i>
                    <a href="tel:0246925408">
                        (024) 6925408
                    </a>
                </div>

                <div class="footer-item">
                    <i class="fas fa-globe"></i>
                    <a href="https://unw.ac.id" target="_blank">
                        www.unw.ac.id
                    </a>
                </div>
            </div>

            <div class="footer-column">
                <h3>Lokasi Kampus</h3>

                <div class="map-wrapper">
                    <iframe
                        class="footer-map"
                        src="https://www.google.com/maps?q=Universitas+Ngudi+Waluyo&output=embed"
                        allowfullscreen
                        loading="lazy">
                    </iframe>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            © {{ date('Y') }}
            Universitas Ngudi Waluyo |
            Sistem Evaluasi Dosen Oleh Mahasiswa (EDOM).
            All Rights Reserved.
        </div>
    </div>
</footer>
