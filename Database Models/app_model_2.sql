-- phpMyAdmin SQL Dump
-- version 4.6.4deb1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Czas generowania: 15 Paź 2016, 17:45
-- Wersja serwera: 5.6.30-1
-- Wersja PHP: 7.0.11-1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Baza danych: `app`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `books`
--

CREATE TABLE `books` (
  `book_id` int(10) UNSIGNED NOT NULL,
  `book_title` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `book_author` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `book_isbn` char(13) COLLATE utf8_unicode_ci NOT NULL,
  `book_category` enum('fic','bio','bai','ckg','his','com','cst','kds','pls','law','rel','rom','sfi','hlt') COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'fiction, biographies, business and investments, cooking, history, computers, crime stories, kids, politics, law, religion, romance, sci-fi, health',
  `book_cover` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `book_user_author` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `bookshelves`
--

CREATE TABLE `bookshelves` (
  `bookshelf_id` int(10) UNSIGNED NOT NULL,
  `bookshelf_latitude` float NOT NULL,
  `bookshelf_longitude` float NOT NULL,
  `bookshelf_name` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `bookshelf_author` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `bookshelves_books`
--

CREATE TABLE `bookshelves_books` (
  `bookshelf_id` int(10) UNSIGNED NOT NULL,
  `book_id` int(10) UNSIGNED NOT NULL,
  `book_adder` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `user_email` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `user_password` char(60) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `user_auth_token` char(20) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `user_signed_in` tinyint(1) NOT NULL DEFAULT '0',
  `user_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `user_score` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_creation_date` datetime NOT NULL,
  `user_account_type` enum('u','m','a') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'u' COMMENT 'user, moderator, admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indeksy dla zrzutów tabel
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`),
  ADD KEY `book_user_author` (`book_user_author`);

--
-- Indexes for table `bookshelves`
--
ALTER TABLE `bookshelves`
  ADD PRIMARY KEY (`bookshelf_id`);

--
-- Indexes for table `bookshelves_books`
--
ALTER TABLE `bookshelves_books`
  ADD UNIQUE KEY `book_id_2` (`book_id`),
  ADD KEY `bookshelf_id` (`bookshelf_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `book_adder` (`book_adder`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `user_email` (`user_email`),
  ADD UNIQUE KEY `user_auth_token` (`user_auth_token`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT dla tabeli `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT dla tabeli `bookshelves`
--
ALTER TABLE `bookshelves`
  MODIFY `bookshelf_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT dla tabeli `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
--
-- Ograniczenia dla zrzutów tabel
--

--
-- Ograniczenia dla tabeli `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`book_user_author`) REFERENCES `users` (`user_id`);

--
-- Ograniczenia dla tabeli `bookshelves_books`
--
ALTER TABLE `bookshelves_books`
  ADD CONSTRAINT `bookshelves_books_ibfk_1` FOREIGN KEY (`bookshelf_id`) REFERENCES `bookshelves` (`bookshelf_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `bookshelves_books_ibfk_3` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `bookshelves_books_ibfk_4` FOREIGN KEY (`book_adder`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
