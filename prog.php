<?php

class tree {
	public $b = array(); // Wh, Bl, P, N, B, R, Q, K
	public $u = array(); // undo
	
	const SP = 0, P = 1, N = 2, K = 3, B = 4, R = 5, Q = 7;
	
	public $repr;
	public $reprVal;
	
	public $wtm;
	public $c; // KQkq
	public $ep;
	public $hm = 0;
	public $fm = 0;
	
	private $kms = array(-33, -31, -18, -14, 14, 18, 31, 33);
	private $lin = array(-16, -1, 1, 16);
	private $dia = array(-17, -15, 15, 17);
	
	function __construct() {
		$this->reset();
		$this->repr = array(0 => ' ', 1 => 'P', 2 => 'N', 3 => 'K', 4 => 'B', 5 => 'R', 7 => 'Q', -1 => 'p', -2 => 'n', -3 => 'k', -4 => 'b', -5 => 'r', -7 => 'q');
		$this->reprVal = array_flip($this->repr);
	}
	
	function reset() {
		$this->b = array_fill(0, 128, 0);
	}
	
	function sp() {
		$this->reset();
		for ($i = 0; $i < 8; $i++) {
			$this->b[$i + 16] = self::P;
			$this->b[0x60 + $i] = -self::P;
		}
		$p = array(5, 2, 4, 7, 3, 4, 2, 5);
		array_splice($this->b, 0x00, 8, $p);
		array_splice($this->b, 0x70, 8, array_map(function($x) { return -$x; }, $p));
		$this->wtm = true;
		$this->c = array(1, 1, 1, 1);
		$this->ep = null;
	}
	
	function fen($fen, $validate = true) {
		$this->reset();
		$p = preg_split('/\s+/', $fen);
		$r = explode('/', $p[0]);
		if (count($r) != 8) {
			if ($validate) {
				throw new Exception("Invalid row length $r");
			}
		}
		for ($i = 0; $i < 8; $i++) {
			$rnk = 7 - $i;
			$f = 0;
			foreach(str_split($r[$i]) as $c) {
				if (isset($this->reprVal[$c])) {
					$this->b[16 * $rnk + $f] = $this->reprVal[$c];
					$f++;
				} else if (is_numeric($c)) {
					$f += $c;
				} else if ($validate) {
					throw new Exception("Invalid character $c");
				}
			}
			if ($f != 8 && $validate) {
				throw new Exception("{$r[$i]} {$f}");
			}
		}
		
		if ($p[1] == 'w') {
			$this->wtm = true;
		} else if ($p[1] == 'b') {
			$this->wtm = false;
		} else if ($validate) {
			throw new Exception("Invalid side to move");
		}
		
		$cs = array('K', 'Q', 'k', 'q');
		if ($validate) {
			foreach (str_split($p[2]) as $pp) {
				if (!in_array($pp, $cs)) {
					throw new Exception("Invalid castling character $pp");
				}
			}
		}
		foreach ($cs as $idx => $val) {
			if (strpos($p[2], $val) !== false) {
				$this->c[$idx] = 1;
			} else {
				$this->c[$idx] = 0;
			}
		}
		
		if ($p[3] == '-') {
			$this->ep = null;
		} else {
			$this->ep = $this->tosq($p[3]);
		}
		
		if (isset($p[4])) {
			if ($validate && !is_numeric($p[4])) {
				throw new Exception("Invalid halfmove character {$p[4]}");
			}
			$this->hm = $p[4];
		}
		
		if (isset($p[5])) {
			if ($validate && !is_numeric($p[5])) {
				throw new Exception("Invalid fullmove character {$p[4]}");
			}
			$this->fm = $p[5];
		}
	}
	
	function pr() {
		$result = $sep = "+-+-+-+-+-+-+-+-+\n"; 
		for ($r = 7; $r >= 0; $r--) {
			$result .= "|";
			for ($f = 0; $f < 8; $f++) {
				$p = $this->b[0x10 * $r + $f];
				$v = $this->repr[abs($p)];
				$result .= $p > 0 ? $v : strtolower($v);
				$result .= "|";
			}
			$result .= "\n$sep";
		}
		return $result;
	}
	
	function prm($m) {
		$result = array();
		for($i = 0; $i < 2; $i++) {
			$r = $m[$i] >> 4;
			$f = $m[$i] & 0x07;
			$result[] = chr(ord('a') + $f) . ($r + 1);
		}
		return implode('', $result);
	}
	
	function tosq($str, $validate = true) {
		if ($validate) {
			if (strlen($str) > 2) {
				throw new Exception("Squares must be exactly 2 characters");
			}
		}
		$cs = str_split($str);
		$f = ord($cs[0]) - ord('a');
		if ($validate && ($f < 0 || $f > 7) ) {
			throw new Exception("Invalid file");
		}
		if ($validate && ($cs[1] < 0 || $cs[1] > 7)) {
			throw new Exception("Invalid rank");
		}
		
		return $r * 16 + $f;
	}
	
	function gplm($wtm) {
		$result = array();
		for ($r = 7; $r >= 0; $r--) {
			for ($f = 0; $f < 8; $f++) {
				$sq = 0x10 * $r + $f;
				$p = $this->b[$sq];
				if ($p == 0) {
					continue;
				} else if ($wtm && $p < 0) {
					continue;
				} else if (!$wtm && $p > 0) {
					continue;
				}
				if (abs($p) == 1) {
					// p
					if ($p == 1) {
						if ($this->b[0x10 * ($r + 1) + $f] != 0) {
							continue;
						}
						$result[] = array(0x10 * $r + $f, 0x10 * ($r + 1) + $f, 0);
						if ($r == 1 && $this->b[0x10 * ($r + 2) + $f] == 0) {
							$result[] = array(0x10 * $r + $f, 0x10 * ($r + 2) + $f, 0);
						}
					} else {
						if ($this->b[0x10 * ($r - 1) + $f] != 0) {
							continue;
						}
						$result[] = array(0x10 * $r + $f, 0x10 * ($r - 1) + $f, 0);
						if ($r == 6 && $this->b[0x10 * ($r - 2) + $f] == 0) {
							$result[] = array(0x10 * $r + $f, 0x10 * ($r - 2) + $f, 0);
						}
					}
					$tds = array(15 * $p, 17 * $p);
					foreach($this->atx($sq, $tds, 1, true) as $pt) {
						if (($this->b[$pt] * $p) < 1) {
							$result[] = array($sq, $pt, $this->b[$pt]);
						}
						if ($pt == $this->ep) {
							$result[] = array($sq, $pt, 0);
						}
					}
					continue;
				} else if (abs($p) == 2) {
					// n
					foreach($this->atx($sq, $this->kms, 1) as $km) {
						if ($this->b[$km] * $p < 1) {
							$result[] = array($sq, $km, $this->b[$km]);
						}
					}
					continue;
				}
				if (abs($p) == 3) {
					$max = 1;
				} else {
					$max = 8;
				}
				// slid & k
				if (abs($p) > 2) {
					if (abs($p) & 2 != 0) {
						// lin
						foreach($this->atx($sq, $this->lin, $max) as $lm) {
							if ($this->b[$lm] * $p <= 0) {
								$result[] = array($sq, $lm, $this->b[$lm]);
							}
						}
					}
					if (abs($p) & 1 != 0) {
						// dia
						foreach($this->atx($sq, $this->dia, $max) as $dm) {
							if ($this->b[$dm] * $p <= 0) {
								$result[] = array($sq, $dm, $this->b[$dm]);
							}
						}
					}
				}
				
				// c
				if (abs($p) == 3) {
					
					if ($p > 0 && $sq != 0x04) {
						continue;
					}
					if ($p < 0 && $sq != 0x74) {
						continue;
					}
					if ($p > 0) {
						if ($this->c[0] && $this->b[0x07] == self::R and $this->atx(0x07, array(-1), 0x05)) {
							$result[] = array(0x04, 0x02);
						}
						if ($this->c[1] && $this->b[0x00] == self::R and $this->atx(0x00, array(1), 0x02)) {
							$result[] = array(0x04, 0x06);
						}
						if ($this->c[2] && $this->b[0x77] == -self::R and $this->atx(0x77, array(-1), 0x75)) {
							$result[] = array(0x74, 0x72);
						}
						if ($this->c[3] && $this->b[0x70] == -self::R and $this->atx(0x70, array(1), 0x72)) {
							$result[] = array(0x74, 0x76);
						}
					}
				}
			}
		}
		
		return $result;
	}
	
	public function atx($sq, $dirs, $max=8, $caps=false) {
		$result = array();
		foreach($dirs as $d) {
			$cnt = 0;
			$sqt = $sq;
			while($cnt++ < $max) {
				$sqt += $d;
				if (!$this->vs($sqt)) {
					break;
				}
				if ($this->b[$sqt] == 0) {
					if (!$caps) {
						$result[] = $sqt;
					}
					continue;
				}
				if ($this->b[$sqt] * $this->b[$sq] < 0) {
					$result[] = $sqt;
				}
				break;
			}
		}
		return $result;
	}
	
	public function vs($sq) {
		return $sq >= 0 && ($sq & 0x88) == 0;
	}
	
	public function m($m, $legal = true) {
		
		$u = array('b' => array());
		$u['b'][$m[0]] = $this->b[$m[0]];
		$u['b'][$m[1]] = $this->b[$m[1]];
		$u['ep'] = $this->ep;
		$u['c'] = $this->c;
		
		if (abs($this->b[$m[0]]) == 1 && $m[1] == $this->ep) {
			$eps = $m[0] & 0xF0 | $m[1] & 0x0F;
			$u['b'][$eps] = $this->b[$eps];
		}
		
		$this->u[] = $u;
		
		// ep
		if (abs($this->b[$m[0]]) == 1 && abs($m[0] - $m[1]) == 32) {
			$this->ep = $m[0] + $m[1] / 2;
		}
		
		// q
		if (!empty($m[2])) {
			$this->b[$m[1]] = $m[2];
		} else {
			$this->b[$m[1]] = $this->b[$m[0]];
		}
		
		// c : TODO

		$this->b[$m[0]] = 0;
		$this->wtm = !$this->wtm;
		
		if ($legal) {
			// TODO: Inefficient
			$ktm = null;
			for($i = 0; $i < 128; $i++) {
				if (($this->wtm && $this->b[$i] == self::K) || (!$this->wtm && $this->b[$i] == -self::K)) {
					$ktm = $i;
					break;
				}
			}
			if (is_null($ktm)) {
				throw new Exception("Side to move king not found");
			}
			if ($this->isatk($ktm)) {
				$this->u();
				return false;
			}
		}

		return true;
	}
	
	public function isatk($sq) {
		$p = $this->b[$sq];
		for($i = 0; $i < 128; $i++) {
			$tp = $this->b[$i];
			if ($tp == 0 || $tp * $p > 0) {
				continue;
			}
			
			// Do pwns here
			if (abs($p) == 1) {
				
			} else if (abs($p) == 2) {
				if (in_array($sq - $i, $this->kms)) {
					return true;
				}
			} else if (abs($p) == 3) {
				if (in_array($sq - $i, $this->dia)) {
					return true;
				}
				if (in_array($sq - $i, $this->lin)) {
					return true;
				}
			}
			if (abs($p) & 4 != 0) {
				if (abs($p) & 2 != 0) {
					// lin
					if (($i & 0xF0 == $sq & 0xF0) || ($i & 0x0F == $sq & 0x0F)) {
						$a = $this->atx($i, $this->lin, 8, true);
						if (in_array($sq, $a)) {
							return true;
						}
					}
				}
				if (abs($p) & 1 != 0) {
					// dia
					if (in_array($sq - $i, $this->dia)) {
						$a = $this->atx($i, $this->dia, 8, true);
						if (in_array($sq, $a)) {
							return true;
						}
					}
				}
			}
			
			// TODO: c
			if (abs($p) == 3) {
				// Check rk
			}
		}
	}
	
	public function u() {
		$u = array_pop($this->u);
		foreach($u['b'] as $sq => $v) {
			$this->b[$sq] = $v;
		}
		
		if (isset($u['ep'])) {
			$this->ep = $u['ep'];
		}
		
		if (isset($u['c'])) {
			$this->c = $u['c'];
		}
		
		$this->wtm = !$this->wtm;
	}
}