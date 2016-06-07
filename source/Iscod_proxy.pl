#**********************************************************************
#* This framework is  under the GNU License.
#*
#* All rights reserved 
#*
#*This file is written by Philippe JAILLON
#*For information, feedback, questions, please contact bissana@gmail.com
#*
#*
#* version 23/1/2014 
#* copyright Philippe JAILLON (ecole des mines de saint etienne, France)
#***********************************************************************

#!/usr/bin/perl

use IO::Socket;
use Encode;
use Time::HiRes qw/ time sleep /;

#use NDBM_File;
use BerkeleyDB;

##################################################################################

package HTTP;

sub new {
	my( $dummy, $sock ) = @_;
	my $self = {};
	$self->{'sock'} = $sock;
	bless $self;
}

sub getFirstLine {
	my( $self ) = @_;

	return $self->{'first_line'} if( defined( $self->{'first_line'}) );

	# else read first line from network
	my $sock = $self->{'sock'};
	$self->{'first_line'} = <$sock>; $self->{'first_line'} =~ s/[\r\n]*$//;
	return $self->{'first_line'};
}

sub getHeaders {
	my( $self ) = @_;
	my $headers = {};

	$self->getFirstLine();

	return $self->{'headers'} if( defined( $self->{'headers'}) );

	while( 1 ) {
		$header = $self->{'sock'}->getline();
		$header =~ s/[\r\n]*$//g;
		my($head,$value) = $header =~ /^([^:\s]+)\s*:\s*([^\r\n]+)/;
		$headers->{$head} = $value if( $head );
		last unless( $header );
	}
	$self->{'headers'} = $headers;
}

sub getBody {
	my( $self ) = @_;
	my $buff = "";
	local $H = $self->getHeaders();
	local $n;

	return $self->{'body'} if( defined( $self->{'body'}) );

	# else read body from network
	if( $H->{'Content-Length'} ) {
		$self->{'body'} =  "";
		$n= $self->{'sock'}->read($self->{'body'}, $H->{'Content-Length'} ) ;
	}
	else {
		while( $buff = $self->{'sock'}->getline() ) {
			$self->{'body'} .= $buff; 
		}
	}
	$self->{'body'};
}

sub toString {
	my( $self ) = @_;
	my $H = $self->getHeaders();

	join( "\r\n", map($_.":".$H->{$_}, keys %$H ), "\r\n" ) . $self->{'body'};
}

sub log {
	my( $self ) = @_;
	print STDERR $self->getFirstLine(), "\n", join( "\n", map("\t".$_.":".$self->{'headers'}->{$_}, keys %{$self->{'headers'}} )), "\n";
}

##################################################################################

package HTTP::Request;
@ISA = qw(HTTP);

sub new {
	shift;
	my( $sock ) = @_;
	my $self = new HTTP( $sock );
	my $dummy, $host;
	local $H;

	$self->getFirstLine();
	$H = $self->getHeaders();
	$self->getBody() if( $H->{'Content-Length'} );

	($self->{'method'},$host,$self->{'url'}, $self->{'proto'}) = $self->{'first_line'} =~ m#^(GET|POST|HEAD)\s+http://([^/]+)(/[^\s]*)\s+([^\n\r]+)#;

	($self->{'host'}, $dummy, $self->{'port'}) = $host =~ /([^:]+)(:(\d+))?/;
	$self->{'port'}=80 unless($self->{'port'});
	$self->{'proto'} = "HTTP/1.0";	# disable HTTP/1.1 features (chrunked transfert)

	delete( $H->{'Proxy-Connection'} );
	delete( $H->{'Accept-Encoding'} );
	#delete( $H->{'If-Modified-Since'} );	# force reload
	# we are not a proxy with keep-alive functionality
	$H->{'Connection'} = "close" if($H->{'Connection'});
	bless $self;
}

sub key {
	my( $self ) = @_;
	($dummy,$key) = $self->{'first_line'} =~ m#^(GET|POST|HEAD)\s+(http://([^/]+)(/[^\s]*))\s+([^\n\r]+)#;
	return $key;
}

sub FinalHost {
	my( $self ) = @_;
	return $self->{'host'} . ":" . $self->{'port'};
}

sub toString {
	my( $self ) = @_;
	my $str = join(" ", $self->{'method'},$self->{'url'}, $self->{'proto'}) ."\r\n". $self->SUPER::toString();
	$str;
}

##################################################################################

package HTTP::Response;
@ISA = qw(HTTP);

sub new {
	my( $dummy, $sock) = @_;
	my $self = new HTTP( $sock );

	$self->getFirstLine();
	$self->getHeaders();
	$self->getBody();

	($self->{'proto'},$self->{'status'}, $self->{'phrase'}) = $self->{'first_line'} =~ m#^(HTTP/1.\d)\s+(\d+)\s+([^\n\r]*)#;
	bless $self;
}

sub toString {
	my( $self ) = @_;
	my $str = join("\r\n", $self->getFirstLine(), $self->SUPER::toString());
	$str;
}

##################################################################################

package IO::Socket::HTTP;
@ISA = qw{IO::Socket::INET};

sub new {
	shift;
	my ($self) = @_;
	bless $self;
}

sub notFound {
	my( $self ) = @_;
	$self->write( "HTTP/1.0 404 Host not found\r\n\r\n" );
	$self->close();
}

##################################################################################

package IO::Socket::HTTP::SERVER;
@ISA = qw{IO::Socket::HTTP};

sub new {
	shift;
	my ($self) = @_;
	bless $self;
}

sub Request {
my ($self) = @_;
	return new HTTP::Request($self);
}

##################################################################################

package IO::Socket::HTTP::CLIENT;
@ISA = qw{IO::Socket::HTTP};

sub new {
	shift;
	my ($host) = @_;
	my $self = new IO::Socket::INET ( 'PeerAddr' => $host );
	unless( $self ) { print STDERR "Sorry, no host $host !\n\n"; return 0; }
	bless $self;
}

sub Answer {
my ($self) = @_;
	return new HTTP::Response($self);
}

##################################################################################

package main;

#$SIG{'CHLD'} = 'IGNORE';

# A -> me -> B
# A <- me <- B

# create a server
# get port number from user, otherwise consider 3128.
 my $portNumber = ':3129';
 if (@ARGV > 0)
 {
     $portNumber = ':'.$ARGV[0];
 }
          
$S = new IO::Socket::INET( 
	'LocalAddr' => $portNumber,
        'ReuseAddr' => 1,
        'Listen'    => 5,
        );

my $lastConnexionTime = time;
my $now;
my $minDelai;

$| = 1;	# disable output buffering

while( 1 ) {
	eval {
		$A = new IO::Socket::HTTP::SERVER( $S->accept() );
		# set alarm when accept new connection 
		local $SIG{ALRM} = sub { die 'Connection Timed Out'; };
  		alarm 20;	# set TimeOut to 20 seconds

		($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
		$Q = $A->Request() ; 
		$Q->log();
		dbmopen(%CACHE,'./cache',0600);
		$cached = $CACHE{$Q->key()};
		if( $cached ) {
			$A->write( $cached );
			print STDERR "Read from cache\n";
		}
		else {
	#		if ($hour > 20 or $hour < 7 or $wday==0 or $wday ==6) #weekend and night
			if ($hour > 20 or $hour < 7) {	
				$minDelai = 0.33;
			}
			else {	
				$minDelai = 2; #working day
			}
			$now = time;
			if (($now - $lastConnexionTime ) < $minDelai) {
				#sleep($minDelai -($now - $lastConnexionTime));
				Time::HiRes::usleep(($minDelai -($now - $lastConnexionTime))*1000000);
			}
			
			$B = new IO::Socket::HTTP::CLIENT( $Q->FinalHost() );
			if( $B ) {
				print STDERR "CheckPoint1\n";
				$B->write( $Q->toString() );
				print STDERR "CheckPoint2\n";
				$R = $B->Answer();
				print STDERR "CheckPoint3\n";
				$R->log();
				print STDERR "CheckPoint4\n";
				$A->write( $R->toString() );
				print STDERR "CheckPoint5\n";
				$B->close();
				if( ($R->getHeaders()->{'Content-Type'} =~ /text/ ) && ($R->{'status'} != 304 )) {
					print STDERR "Add to cache, its text, status [", $R->{'status'} , "]\n";
					$CACHE{$Q->key()} = $R->toString();
					print STDERR "CheckPoint6\n";
				}
			}
			else {
				$A->notFound();
				print STDERR "CheckPoint7\n";
			}
			$lastConnexionTime = time;
		}
		$A->close();
		dbmclose(%CACHE);
		print STDERR "CheckPoint8\n";
		print STDERR "\n";

		alarm 0; # Cancel current alarm
	};
	alarm 0; # protection against race condition
	if( $@ ) {
		warn $@;
		eval { 
			$A->close() if( $A );
			$B->close() if( $B );
		};
		$@='';
	}
}

1;
