SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

CREATE TABLE IF NOT EXISTS `comments` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `content` mediumtext NOT NULL,
  `parent` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `parent` (`parent`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;


INSERT INTO `comments` (`id`, `content`, `parent`) VALUES
(1, 'You guys are my absolute heroes! I wish I could give you my first born son or daughter.', 1),
(3, 'This is a test!', 1),
(4, 'hi there!', 1),
(5, 'This is a testie', 1),
(6, 'testie', 2);

CREATE TABLE IF NOT EXISTS `news` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(60) NOT NULL,
  `content` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

INSERT INTO `news` (`id`, `title`, `content`) VALUES
(1, 'Banana Bit Technologies', 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. It has sum has been the industry''s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged!\r\n'),
(2, 'World Premier', 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. It has sum has been the industry''s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged!\r\n'),
(3, 'This is a test for me', 'to see if the cached page will actually update.'),
(4, 'And Again', 'We go round and round and round'),
(5, 'test', 'yay');
