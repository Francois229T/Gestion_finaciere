-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 08, 2025 at 09:45 PM
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
  `responsable_titre` varchar(100) DEFAULT NULL,
  `organisateur_titre` varchar(100) DEFAULT NULL,
  `financier_titre` varchar(100) DEFAULT NULL,
  `note_generatrice` text DEFAULT NULL,
  `periode_debut` date NOT NULL,
  `periode_fin` date NOT NULL,
  `centre` varchar(100) NOT NULL,
  `taux_journalier` decimal(12,2) DEFAULT NULL,
  `forfait` decimal(12,2) DEFAULT NULL,
  `frais_deplacement` decimal(12,2) DEFAULT NULL,
  `nb_jours_deplacement` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comptes_bancaires`
--

CREATE TABLE `comptes_bancaires` (
  `id` int(11) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `banque` varchar(100) NOT NULL,
  `numero_compte` varchar(50) NOT NULL,
  `rib_pdf_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `comptes_bancaires`
--

INSERT INTO `comptes_bancaires` (`id`, `participant_id`, `banque`, `numero_compte`, `rib_pdf_path`) VALUES
(4, 7, '', '', '');

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
(7, 'individu', '2025-07-07 11:24:10');

-- --------------------------------------------------------

--
-- Table structure for table `participations`
--

CREATE TABLE `participations` (
  `id` int(11) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `activite_id` int(11) NOT NULL,
  `compte_id` int(11) NOT NULL,
  `titre` varchar(100) NOT NULL,
  `nb_jours_copies` int(11) DEFAULT NULL,
  `forfait_participant` decimal(12,2) DEFAULT NULL,
  `montant_paye` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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
  `registre_commerce` varchar(50) DEFAULT NULL,
  `representant_legal` varchar(100) DEFAULT NULL,
  `adresse` varchar(200) DEFAULT NULL,
  `IFU` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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
(7, 'EZIN', 'Yannick', '2005-01-01', 'Azovè', 'Licence Informatique');

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
  ADD PRIMARY KEY (`id`),
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comptes_bancaires`
--
ALTER TABLE `comptes_bancaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `documents_activite`
--
ALTER TABLE `documents_activite`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `participants`
--
ALTER TABLE `participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `participations`
--
ALTER TABLE `participations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personnes_physiques`
--
ALTER TABLE `personnes_physiques`
  MODIFY `participant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  ADD CONSTRAINT `participations_ibfk_3` FOREIGN KEY (`compte_id`) REFERENCES `comptes_bancaires` (`id`) ON DELETE CASCADE;

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
