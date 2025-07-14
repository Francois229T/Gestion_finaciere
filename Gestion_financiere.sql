-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 14, 2025 at 04:53 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `Gestion_financiere`
--

-- --------------------------------------------------------

--
-- Table structure for table `activites`
--

CREATE TABLE `activites` (
  `id` int(11) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `responsable_titre` varchar(100) NOT NULL,
  `organisateur_titre` varchar(100) NOT NULL,
  `financier_titre` varchar(100) NOT NULL,
  `note_generatrice` text NOT NULL,
  `periode_debut` date NOT NULL,
  `periode_fin` date NOT NULL,
  `centre` varchar(100) NOT NULL,
  `taux_journalier` decimal(12,2) DEFAULT NULL,
  `forfait` decimal(12,2) DEFAULT NULL,
  `frais_deplacement` decimal(12,2) DEFAULT NULL,
  `nb_jours_deplacement` int(11) DEFAULT NULL,
  `nb_jours_copies` int(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `activites`
--

INSERT INTO `activites` (`id`, `nom`, `description`, `responsable_titre`, `organisateur_titre`, `financier_titre`, `note_generatrice`, `periode_debut`, `periode_fin`, `centre`, `taux_journalier`, `forfait`, `frais_deplacement`, `nb_jours_deplacement`, `nb_jours_copies`) VALUES
(11, 'Examen BAC', 'Test Description', 'Bignon', 'Janvier', 'François', '/opt/lampp/htdocs/projet_gestion_administrative/uploads/notes/note_generatrice_6873ba3b6b6de7.03097060.pdf', '2026-02-17', '2026-02-24', 'Porto-Novo', NULL, NULL, NULL, NULL, NULL),
(12, 'Examen BAC', 'Test Description', 'Bignon', 'Janvier', 'François', '/opt/lampp/htdocs/projet_gestion_administrative/uploads/notes/note_generatrice_6873ba75ad59b1.92203920.pdf', '2026-02-02', '2026-02-12', 'Calavi', NULL, NULL, NULL, NULL, NULL),
(13, 'Examen BAC', 'Test Description', 'Bignon', 'Janvier', 'François', '/opt/lampp/htdocs/projet_gestion_administrative/uploads/notes/note_generatrice_6873baec1d4911.81146385.pdf', '2026-02-02', '2026-02-12', 'Calavi', NULL, NULL, NULL, NULL, NULL),
(14, 'Examen BAC', 'Test Description', 'Bignon', 'Janvier', 'François', '/opt/lampp/htdocs/projet_gestion_administrative/uploads/notes/note_generatrice_6873bb39bf8a45.21475703.pdf', '2026-02-02', '2026-02-12', 'Calavi', NULL, NULL, NULL, NULL, NULL),
(15, 'Correction CEP', 'Test Description', 'Franck', 'Edouard', 'Emmanuel', '/opt/lampp/htdocs/projet_gestion_administrative/uploads/notes/note_generatrice_6874c0be008b55.51764598.pdf', '2027-05-16', '2027-05-19', 'Abomey-Calavi', NULL, NULL, NULL, NULL, NULL),
(16, 'Correction CEP', 'Test Description', 'Franck', 'Edouard', 'Emmanuel', '/opt/lampp/htdocs/projet_gestion_administrative/uploads/notes/note_generatrice_687503ac23cb14.09907391.pdf', '2027-05-16', '2027-05-19', 'Abomey-Calavi', NULL, NULL, NULL, NULL, NULL),
(17, 'Concours Mathématiques', 'Test Description', 'Robert', 'Manoel', 'Richard', '/opt/lampp/htdocs/projet_gestion_administrative/uploads/notes/note_generatrice_68750659cd3387.03920070.pdf', '2027-05-25', '2027-05-29', 'Porto', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `comptes_bancaires`
--

CREATE TABLE `comptes_bancaires` (
  `id_compte` int(11) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `banque` varchar(100) NOT NULL,
  `numero_compte` varchar(50) NOT NULL,
  `rib_pdf_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `comptes_bancaires`
--

INSERT INTO `comptes_bancaires` (`id_compte`, `participant_id`, `banque`, `numero_compte`, `rib_pdf_path`) VALUES
(1, 1, 'ECOBANK', 'EC123654', '/opt/lampp/htdocs/projet_gestion_administrative/uploads/ribs/rib_686fc28d9fb3f9.24518334_74LS02.pdf'),
(2, 1, 'UBA', 'UB123654', '/opt/lampp/htdocs/projet_gestion_administrative/uploads/ribs/rib_686fc28d9fceb9.26398443_74LS02.pdf'),
(3, 1, 'NSIA', 'NS123654', '/opt/lampp/htdocs/projet_gestion_administrative/uploads/ribs/rib_686fc28d9fe832.65919276_74LS02.pdf'),
(4, 3, 'ECOBANK', 'EC1010001', '/opt/lampp/htdocs/projet_gestion_administrative/uploads/ribs/rib_686fe8ba1ae050.68281717_74LS02.pdf'),
(5, 4, 'ECOBANK', 'EC1010001', '/opt/lampp/htdocs/projet_gestion_administrative/uploads/ribs/rib_686fe9f88a8fc1.52771799_74LS02.pdf'),
(6, 5, 'UBA', 'UA123654789', '/opt/lampp/htdocs/projet_gestion_administrative/uploads/ribs/rib_6874bfd8340c00.76355175_74LS02.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `documents_activite`
--

CREATE TABLE `documents_activite` (
  `id` int(11) NOT NULL,
  `activite_id` int(11) NOT NULL,
  `type_document` varchar(50) NOT NULL,
  `chemin_pdf` varchar(255) NOT NULL,
  `date_generation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `participants`
--

CREATE TABLE `participants` (
  `id` int(11) NOT NULL,
  `type` enum('individu','personne_morale','etat') NOT NULL,
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `participants`
--

INSERT INTO `participants` (`id`, `type`, `date_creation`) VALUES
(1, 'individu', '2025-07-10 14:39:25'),
(3, 'personne_morale', '2025-07-10 17:22:17'),
(4, 'personne_morale', '2025-07-10 17:27:36'),
(5, 'individu', '2025-07-14 09:29:12');

-- --------------------------------------------------------

--
-- Table structure for table `participations`
--

CREATE TABLE `participations` (
  `id` int(11) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `activite_id` int(11) NOT NULL,
  `compte_id` int(11) NOT NULL,
  `type_participant` enum('individu','personne_morale','etat') NOT NULL,
  `titre` varchar(100) NOT NULL,
  `nb_jours_copies` int(11) NOT NULL,
  `taux_journalier_copie` decimal(10,0) NOT NULL,
  `forfait_participant` decimal(12,2) DEFAULT NULL,
  `nb_jours_deplacement` int(11) NOT NULL,
  `frais_deplacement` decimal(12,2) NOT NULL,
  `date_enregistrement` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `participations`
--

INSERT INTO `participations` (`id`, `participant_id`, `activite_id`, `compte_id`, `type_participant`, `titre`, `nb_jours_copies`, `taux_journalier_copie`, `forfait_participant`, `nb_jours_deplacement`, `frais_deplacement`, `date_enregistrement`) VALUES
(1, 5, 17, 6, 'individu', 'Superviseur', 10, 50000, 5000.00, 4, 5000.00, '2025-07-14 15:31:54');

-- --------------------------------------------------------

--
-- Table structure for table `personnes_etat`
--

CREATE TABLE `personnes_etat` (
  `participant_id` int(11) NOT NULL,
  `matricule` varchar(50) NOT NULL,
  `ministere` varchar(100) DEFAULT NULL,
  `grade` varchar(50) DEFAULT NULL,
  `IFU` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personnes_morales`
--

CREATE TABLE `personnes_morales` (
  `participant_id` int(11) NOT NULL,
  `denomination` varchar(150) NOT NULL,
  `representant_legal` varchar(100) NOT NULL,
  `adresse` varchar(200) NOT NULL,
  `IFU` varchar(50) NOT NULL,
  `contact_email` varchar(56) NOT NULL,
  `contact_tel` int(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `personnes_morales`
--

INSERT INTO `personnes_morales` (`participant_id`, `denomination`, `representant_legal`, `adresse`, `IFU`, `contact_email`, `contact_tel`) VALUES
(3, 'SAS Society', 'Roger AHOU', 'Cotonou', 'AS123654789', 'roger@gmail.com', 150496246),
(4, 'SAS Society', 'Roger AHOU', 'Cotonou', 'AS123654789', 'roger@gmail.com', 150496246);

-- --------------------------------------------------------

--
-- Table structure for table `personnes_physiques`
--

CREATE TABLE `personnes_physiques` (
  `participant_id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `date_naissance` date NOT NULL,
  `lieu_naissance` varchar(100) NOT NULL,
  `diplome` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `personnes_physiques`
--

INSERT INTO `personnes_physiques` (`participant_id`, `nom`, `prenom`, `date_naissance`, `lieu_naissance`, `diplome`) VALUES
(1, 'EZIN', 'Yannick', '2005-01-01', 'Azovè', 'Licence Informatique'),
(5, 'AHOSSOU', 'Kévin', '2000-12-05', 'Come', 'Master IA');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activites`
--
ALTER TABLE `activites`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `comptes_bancaires`
--
ALTER TABLE `comptes_bancaires`
  ADD PRIMARY KEY (`id_compte`),
  ADD KEY `participant_id` (`participant_id`);

--
-- Indexes for table `documents_activite`
--
ALTER TABLE `documents_activite`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activite_id` (`activite_id`);

--
-- Indexes for table `participants`
--
ALTER TABLE `participants`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `participations`
--
ALTER TABLE `participations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `participant_id` (`participant_id`),
  ADD KEY `activite_id` (`activite_id`),
  ADD KEY `compte_id` (`compte_id`);

--
-- Indexes for table `personnes_etat`
--
ALTER TABLE `personnes_etat`
  ADD PRIMARY KEY (`participant_id`);

--
-- Indexes for table `personnes_morales`
--
ALTER TABLE `personnes_morales`
  ADD PRIMARY KEY (`participant_id`);

--
-- Indexes for table `personnes_physiques`
--
ALTER TABLE `personnes_physiques`
  ADD PRIMARY KEY (`participant_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activites`
--
ALTER TABLE `activites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `comptes_bancaires`
--
ALTER TABLE `comptes_bancaires`
  MODIFY `id_compte` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `documents_activite`
--
ALTER TABLE `documents_activite`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `participants`
--
ALTER TABLE `participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `participations`
--
ALTER TABLE `participations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `personnes_physiques`
--
ALTER TABLE `personnes_physiques`
  MODIFY `participant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comptes_bancaires`
--
ALTER TABLE `comptes_bancaires`
  ADD CONSTRAINT `comptes_bancaires_ibfk_1` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `documents_activite`
--
ALTER TABLE `documents_activite`
  ADD CONSTRAINT `documents_activite_ibfk_1` FOREIGN KEY (`activite_id`) REFERENCES `activites` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `participations`
--
ALTER TABLE `participations`
  ADD CONSTRAINT `participations_ibfk_1` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `participations_ibfk_2` FOREIGN KEY (`activite_id`) REFERENCES `activites` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `participations_ibfk_3` FOREIGN KEY (`compte_id`) REFERENCES `comptes_bancaires` (`id_compte`) ON DELETE CASCADE;

--
-- Constraints for table `personnes_etat`
--
ALTER TABLE `personnes_etat`
  ADD CONSTRAINT `personnes_etat_ibfk_1` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `personnes_morales`
--
ALTER TABLE `personnes_morales`
  ADD CONSTRAINT `personnes_morales_ibfk_1` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `personnes_physiques`
--
ALTER TABLE `personnes_physiques`
  ADD CONSTRAINT `personnes_physiques_ibfk_1` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
