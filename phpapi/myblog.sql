CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `lastlogin` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

INSERT INTO 
  `users` (`id`, `firstname`, `lastname`, `email`, `username`, `password`) 
VALUES
  (1, 'Chris', 'Crooke', 'chris@ccrooke.com', 'ccrooke', '4Testing!'),
  (2, 'Erin', 'Smith', 'erin@ccrooke.com', 'esmith', '4Testing!'),
  (3, 'Tyra', 'Jackson', 'tyra@ccrooke.com', 'tjackson', '4Testing!'),
  (4, 'Joe', 'Smith', 'joe@ccrooke.com', 'jsmith', '4Testing!')
